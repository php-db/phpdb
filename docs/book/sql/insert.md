# Insert Queries

The `Insert` class provides an API for building SQL INSERT statements.

## Insert API

```php title="Insert Class Definition"
class Insert extends AbstractPreparableSql
    implements SqlInterface, PreparableSqlInterface
{
    final public const VALUES_MERGE = 'merge';
    final public const VALUES_SET   = 'set';

    public function __construct(
        string|TableIdentifier|null $table = null
    );
    public function into(
        TableIdentifier|string|array $table
    ) : static;
    public function columns(array $columns) : static;
    public function values(
        array|Select $values,
        string $flag = self::VALUES_SET
    ) : static;
    public function select(Select $select) : static;
    public function getRawState(
        ?string $key = null
    ) : TableIdentifier|string|array;
}
```

As with `Select`, the table may be provided during instantiation or
via the `into()` method.

## Basic Usage

```php title="Creating a Basic Insert Statement"
use PhpDb\Sql\Sql;

$sql = new Sql($adapter);
$insert = $sql->insert('users');

$insert->values([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'created_at' => date('Y-m-d H:i:s'),
]);

$statement = $sql->prepareStatementForSqlObject($insert);
$statement->execute();
```

Produces:

```sql title="Generated SQL Output"
INSERT INTO users (username, email, created_at) VALUES (?, ?, ?)
```

## columns()

The `columns()` method explicitly sets which columns will receive
values:

```php title="Setting Valid Columns"
$insert->columns(['foo', 'bar']); // set the valid columns
```

When using `columns()`, only the specified columns will be included
even if more values are provided:

```php title="Restricting Columns with Validation"
$insert->columns(['username', 'email']);
$insert->values([
    'username' => 'john',
    'email' => 'john@example.com',
    'extra_field' => 'ignored', // This will be ignored
]);
```

## values()

The default behavior of values is to set the values.
Successive calls will not preserve values from previous calls.

```php title="Setting Values for Insert"
$insert->values([
    'col_1' => 'value1',
    'col_2' => 'value2',
]);
```

To merge values with previous calls, provide the appropriate flag:
`PhpDb\Sql\Insert::VALUES_MERGE`

```php title="Merging Values from Multiple Calls"
$insert->values(['col_1' => 'value1'], $insert::VALUES_SET);
$insert->values(['col_2' => 'value2'], $insert::VALUES_MERGE);
```

This produces:

```sql title="Merged Values SQL Output"
INSERT INTO table (col_1, col_2) VALUES (?, ?)
```

## select()

The `select()` method enables INSERT INTO ... SELECT statements,
copying data from one table to another.

```php title="INSERT INTO SELECT Statement"
$select = $sql->select('tempUsers')
    ->columns(['username', 'email', 'createdAt'])
    ->where(['imported' => false]);

$insert = $sql->insert('users');
$insert->columns(['username', 'email', 'createdAt']);
$insert->select($select);
```

Produces:

```sql title="INSERT SELECT SQL Output"
INSERT INTO users (username, email, createdAt)
SELECT username, email, createdAt
FROM tempUsers WHERE imported = 0
```

Alternatively, you can pass the Select object directly to `values()`:

```php title="Passing Select to values() Method"
$insert->values($select);
```

Important: The column order must match between INSERT columns and
SELECT columns.

## Property-style Column Access

The Insert class supports property-style access to columns as an
alternative to using `values()`:

```php title="Using Property-style Column Access"
$insert = $sql->insert('users');
$insert->name = 'John';
$insert->email = 'john@example.com';

if (isset($insert->name)) {
    $value = $insert->name;
}

unset($insert->email);
```

This is equivalent to:

```php title="Equivalent values() Method Call"
$insert->values([
    'name' => 'John',
    'email' => 'john@example.com',
]);
```

## InsertIgnore

The `InsertIgnore` class provides MySQL-specific INSERT IGNORE syntax,
which silently ignores rows that would cause duplicate key errors.

```php title="Using InsertIgnore for Duplicate Prevention"
use PhpDb\Sql\InsertIgnore;

$insert = new InsertIgnore('users');
$insert->values([
    'username' => 'john',
    'email' => 'john@example.com',
]);
```

Produces:

```sql title="INSERT IGNORE SQL Output"
INSERT IGNORE INTO users (username, email) VALUES (?, ?)
```

If a row with the same username or email already exists and there is a
unique constraint, the insert will be silently skipped rather than
producing an error.

Note: INSERT IGNORE is MySQL-specific. Other databases may use
different syntax for this behavior
(e.g., INSERT ... ON CONFLICT DO NOTHING in PostgreSQL).

## Examples

```php title="Basic insert with prepared statement"
$insert = $sql->insert('products');
$insert->values([
    'name' => 'Widget',
    'price' => 29.99,
    'category_id' => 5,
    'created_at' => new Expression('NOW()'),
]);

$statement = $sql->prepareStatementForSqlObject($insert);
$result = $statement->execute();

// Get the last insert ID
$lastId = $adapter->getDriver()->getLastGeneratedValue();
```

```php title="Insert with expressions"
$insert = $sql->insert('logs');
$insert->values([
    'message' => 'User logged in',
    'created_at' => new Expression('NOW()'),
    'ip_hash' => new Expression('MD5(?)', ['192.168.1.1']),
]);
```

```php title="Bulk insert from select"
// Copy active users to an archive table
$select = $sql->select('users')
    ->columns(['id', 'username', 'email', 'created_at'])
    ->where(['status' => 'active']);

$insert = $sql->insert('users_archive');
$insert->columns([
    'user_id',
    'username',
    'email',
    'original_created_at'
]);
$insert->select($select);

$statement = $sql->prepareStatementForSqlObject($insert);
$statement->execute();
```

```php title="Conditional insert with InsertIgnore"
// Import users, skipping duplicates
$users = [
    ['username' => 'alice', 'email' => 'alice@example.com'],
    ['username' => 'bob', 'email' => 'bob@example.com'],
];

foreach ($users as $userData) {
    $insert = new InsertIgnore('users');
    $insert->values($userData);

    $statement = $sql->prepareStatementForSqlObject($insert);
    $statement->execute();
}
```
