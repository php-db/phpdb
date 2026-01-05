# Update and Delete Queries

## Update

The `Update` class provides an API for building SQL UPDATE statements.

```php title="Update API"
class Update extends AbstractPreparableSql
    implements SqlInterface, PreparableSqlInterface
{
    final public const VALUES_MERGE = 'merge';
    final public const VALUES_SET   = 'set';

    public Where $where;

    public function __construct(
        string|TableIdentifier|null $table = null
    );
    public function table(
        TableIdentifier|string|array $table
    ) : static;
    public function set(
        array $values,
        string|int $flag = self::VALUES_SET
    ) : static;
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ) : static;
    public function join(
        array|string|TableIdentifier $name,
        string $on,
        string $type = Join::JOIN_INNER
    ) : static;
    public function getRawState(?string $key = null) : mixed;
}
```

```php title="Basic Usage"
use PhpDb\Sql\Sql;

$sql = new Sql($adapter);
$update = $sql->update('users');

$update->set(['status' => 'inactive']);
$update->where(['id' => 123]);

$statement = $sql->prepareStatementForSqlObject($update);
$statement->execute();
```

Produces:

```sql title="Generated SQL for basic update"
UPDATE users SET status = ? WHERE id = ?
```

### set()

```php title="Setting multiple values"
$update->set(['foo' => 'bar', 'baz' => 'bax']);
```

The `set()` method accepts a flag parameter to control merging behavior:

```php title="Controlling merge behavior with VALUES_SET and VALUES_MERGE"
$update->set(['status' => 'active'], Update::VALUES_SET);
$update->set(
    ['updatedAt' => new Expression('NOW()')],
    Update::VALUES_MERGE
);
```

When using `VALUES_MERGE`, you can optionally specify a numeric
priority to control the order of SET clauses:

```php title="Using numeric priority to control SET clause ordering"
$update->set(['counter' => 1], 100);
$update->set(['status' => 'pending'], 50);
$update->set(['flag' => true], 75);
```

Produces SET clauses in priority order (50, 75, 100):

```sql title="Generated SQL showing priority-based ordering"
UPDATE table SET status = ?, flag = ?, counter = ?
```

This is useful when the order of SET operations matters for certain
database operations or triggers.

### where()

The `where()` method works the same as in Select queries.
See the [Where and Having](where-having.md) documentation for full
details.

```php title="Using various where clause methods"
$update->where(['id' => 5]);
$update->where->equalTo('status', 'active');
$update->where(function ($where) {
    $where->greaterThan('age', 18);
});
```

### join()

The Update class supports JOIN clauses for multi-table updates:

```php title="Basic JOIN syntax"
$update->join('bar', 'foo.id = bar.foo_id', Update::JOIN_LEFT);
```

Example:

```php title="Update with INNER JOIN on customers table"
$update = $sql->update('orders');
$update->set(['status' => 'cancelled']);
$update->join(
    'customers',
    'orders.customerId = customers.id',
    Join::JOIN_INNER
);
$update->where(['customers.status' => 'inactive']);
```

Produces:

```sql title="Generated SQL for update with JOIN"
UPDATE orders
INNER JOIN customers ON orders.customerId = customers.id
SET status = ?
WHERE customers.status = ?
```

Note: JOIN support in UPDATE statements varies by database platform.
MySQL and PostgreSQL support this syntax,
while some other databases may not.

## Delete

The `Delete` class provides an API for building SQL DELETE statements.

```php title="Delete API"
class Delete extends AbstractPreparableSql
    implements SqlInterface, PreparableSqlInterface
{
    public Where $where;

    public function __construct(
        string|TableIdentifier|null $table = null
    );
    public function from(
        TableIdentifier|string|array $table
    ) : static;
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ) : static;
    public function getRawState(?string $key = null) : mixed;
}
```

```php title="Delete Basic Usage"
use PhpDb\Sql\Sql;

$sql = new Sql($adapter);
$delete = $sql->delete('users');

$delete->where(['id' => 123]);

$statement = $sql->prepareStatementForSqlObject($delete);
$statement->execute();
```

Produces:

```sql title="Generated SQL for basic delete"
DELETE FROM users WHERE id = ?
```

### Delete where()

The `where()` method works the same as in Select queries.
See the [Where and Having](where-having.md) documentation for full
details.

```php title="Using where conditions in delete statements"
$delete->where(['status' => 'deleted']);
$delete->where->lessThan('created_at', '2020-01-01');
```

## Safety Features

Both Update and Delete classes include empty WHERE protection by
default, which prevents accidental mass updates or deletes.

```php title="Checking empty WHERE protection status"
$update = $sql->update('users');
$update->set(['status' => 'deleted']);
// No where clause - this could update ALL rows!

$state = $update->getRawState();
$protected = $state['emptyWhereProtection'];
```

Most database drivers will prevent execution of UPDATE or DELETE
statements without a WHERE clause when this protection is enabled.
Always include a WHERE clause:

```php title="Adding WHERE clause for safe operations"
$update->where(['id' => 123]);

$delete = $sql->delete('logs');
$delete->where->lessThan('createdAt', '2020-01-01');
```

## Examples

```php title="Update with expressions"
$update = $sql->update('products');
$update->set([
    'view_count' => new Expression('view_count + 1'),
    'last_viewed' => new Expression('NOW()'),
]);
$update->where(['id' => $productId]);
```

Produces:

```sql title="Generated SQL for update with expressions"
UPDATE products
SET view_count = view_count + 1, last_viewed = NOW()
WHERE id = ?
```

```php title="Conditional update"
$update = $sql->update('orders');
$update->set(['status' => 'shipped']);
$update->where(function ($where) {
    $where->equalTo('status', 'processing')
        ->and
        ->lessThan(
            'created_at',
            new Expression('NOW() - INTERVAL 7 DAY')
        );
});
```

```php title="Update with JOIN"
$update = $sql->update('products');
$update->set(['products.is_featured' => true]);
$update->join('categories', 'products.category_id = categories.id');
$update->where(['categories.name' => 'Electronics']);
```

```php title="Delete old records"
$delete = $sql->delete('sessions');
$delete->where->lessThan(
    'last_activity',
    new Expression('NOW() - INTERVAL 24 HOUR')
);

$statement = $sql->prepareStatementForSqlObject($delete);
$result = $statement->execute();
$deletedCount = $result->getAffectedRows();
```

```php title="Delete with complex conditions"
$delete = $sql->delete('users');
$delete->where(function ($where) {
    $where->nest()
        ->equalTo('status', 'pending')
        ->and
        ->lessThan('created_at', '2023-01-01')
    ->unnest()
    ->or
    ->nest()
        ->equalTo('status', 'banned')
        ->and
        ->isNull('appeal_date')
    ->unnest();
});
```

Produces:

```sql title="Generated SQL for delete with complex conditions"
DELETE FROM users
WHERE (status = 'pending' AND created_at < '2023-01-01')
   OR (status = 'banned' AND appeal_date IS NULL)
```

```php title="Bulk operations with transactions"
$connection = $adapter->getDriver()->getConnection();
$connection->beginTransaction();

try {
    // Update related records
    $update = $sql->update('order_items');
    $update->set(['status' => 'cancelled']);
    $update->where(['order_id' => $orderId]);
    $sql->prepareStatementForSqlObject($update)->execute();

    // Delete the order
    $delete = $sql->delete('orders');
    $delete->where(['id' => $orderId]);
    $sql->prepareStatementForSqlObject($delete)->execute();

    $connection->commit();
} catch (\Exception $e) {
    $connection->rollback();
    throw $e;
}
```
