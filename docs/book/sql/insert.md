# Insert Queries

The `Insert` class provides an API for building SQL INSERT statements.

## Insert API

### Insert Class Definition

```php
class Insert extends AbstractPreparableSql implements SqlInterface, PreparableSqlInterface
{
    final public const VALUES_MERGE = 'merge';
    final public const VALUES_SET   = 'set';

    public function __construct(string|TableIdentifier|null $table = null);
    public function into(TableIdentifier|string|array $table) : static;
    public function columns(array $columns) : static;
    public function values(
        array|Select $values,
        string $flag = self::VALUES_SET
    ) : static;
    public function select(Select $select) : static;
    public function getRawState(?string $key = null) : TableIdentifier|string|array;
}
```

As with `Select`, the table may be provided during instantiation or via the
`into()` method.

## Basic Usage

### Creating a Basic Insert Statement

```php
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

### Generated SQL Output

```sql
INSERT INTO users (username, email, created_at) VALUES (?, ?, ?)
```

## columns()

The `columns()` method explicitly sets which columns will receive values:

### Setting Valid Columns

```php
$insert->columns(['foo', 'bar']); // set the valid columns
```

When using `columns()`, only the specified columns will be included even if more values are provided:

### Restricting Columns with Validation

```php
$insert->columns(['username', 'email']);
$insert->values([
    'username' => 'john',
    'email' => 'john@example.com',
    'extra_field' => 'ignored', // This will be ignored
]);
```

## values()

The default behavior of values is to set the values. Successive calls will not
preserve values from previous calls.

### Setting Values for Insert

```php
$insert->values([
    'col_1' => 'value1',
    'col_2' => 'value2',
]);
```

To merge values with previous calls, provide the appropriate flag:
`PhpDb\Sql\Insert::VALUES_MERGE`

### Merging Values from Multiple Calls

```php
$insert->values(['col_1' => 'value1'], $insert::VALUES_SET);
$insert->values(['col_2' => 'value2'], $insert::VALUES_MERGE);
```

This produces:

### Merged Values SQL Output

```sql
INSERT INTO table (col_1, col_2) VALUES (?, ?)
```

## select()

The `select()` method enables INSERT INTO ... SELECT statements, copying data
from one table to another.

### INSERT INTO SELECT Statement

```php
$select = $sql->select('tempUsers')
    ->columns(['username', 'email', 'createdAt'])
    ->where(['imported' => false]);

$insert = $sql->insert('users');
$insert->columns(['username', 'email', 'createdAt']);
$insert->select($select);
```

Produces:

### INSERT SELECT SQL Output

```sql
INSERT INTO users (username, email, createdAt)
SELECT username, email, createdAt FROM tempUsers WHERE imported = 0
```

Alternatively, you can pass the Select object directly to `values()`:

### Passing Select to values() Method

```php
$insert->values($select);
```

Important: The column order must match between INSERT columns and SELECT columns.

## Property-style Column Access

The Insert class supports property-style access to columns as an alternative to
using `values()`:

### Using Property-style Column Access

```php
$insert = $sql->insert('users');
$insert->name = 'John';
$insert->email = 'john@example.com';

if (isset($insert->name)) {
    $value = $insert->name;
}

unset($insert->email);
```

This is equivalent to:

### Equivalent values() Method Call

```php
$insert->values([
    'name' => 'John',
    'email' => 'john@example.com',
]);
```

## InsertIgnore

The `InsertIgnore` class provides MySQL-specific INSERT IGNORE syntax, which
silently ignores rows that would cause duplicate key errors.

### Using InsertIgnore for Duplicate Prevention

```php
use PhpDb\Sql\InsertIgnore;

$insert = new InsertIgnore('users');
$insert->values([
    'username' => 'john',
    'email' => 'john@example.com',
]);
```

Produces:

### INSERT IGNORE SQL Output

```sql
INSERT IGNORE INTO users (username, email) VALUES (?, ?)
```

If a row with the same username or email already exists and there is a unique
constraint, the insert will be silently skipped rather than producing an error.

Note: INSERT IGNORE is MySQL-specific. Other databases may use different syntax
for this behavior (e.g., INSERT ... ON CONFLICT DO NOTHING in PostgreSQL).

## Examples

### Basic insert with prepared statement

```php
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

### Insert with expressions

```php
$insert = $sql->insert('logs');
$insert->values([
    'message' => 'User logged in',
    'created_at' => new Expression('NOW()'),
    'ip_hash' => new Expression('MD5(?)', ['192.168.1.1']),
]);
```

### Bulk insert from select

```php
// Copy active users to an archive table
$select = $sql->select('users')
    ->columns(['id', 'username', 'email', 'created_at'])
    ->where(['status' => 'active']);

$insert = $sql->insert('users_archive');
$insert->columns(['user_id', 'username', 'email', 'original_created_at']);
$insert->select($select);

$statement = $sql->prepareStatementForSqlObject($insert);
$statement->execute();
```

### Conditional insert with InsertIgnore

```php
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
