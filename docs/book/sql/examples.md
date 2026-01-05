# Examples and Troubleshooting

## Common Patterns and Best Practices

### Handling Column Name Conflicts in JOINs

When joining tables with columns that have the same name,
explicitly specify column aliases to avoid ambiguity:

```php
$select->from(['u' => 'users'])
    ->columns([
        'userId' => 'id',
        'userName' => 'name',
        'userEmail' => 'email',
    ])
    ->join(
        ['o' => 'orders'],
        'u.id = o.userId',
        [
            'orderId' => 'id',
            'orderDate' => 'createdAt',
            'orderAmount' => 'amount',
        ]
    );
```

This prevents confusion and ensures all columns are accessible in the
result set.

### Working with NULL Values

NULL requires special handling in SQL. Use the appropriate predicates:

```php
$select->where(['deletedAt' => null]);

$select->where->isNull('deletedAt')
    ->or
    ->lessThan('deletedAt', new Expression('NOW()'));
```

In UPDATE statements:

```php title="Setting NULL Values in UPDATE"
$update->set(['optionalField' => null]);
```

In comparisons, remember that `column = NULL` does not work in SQL;
you must use `IS NULL`:

```php title="Checking for NULL or Empty Values"
$select->where->nest()
    ->isNull('field')
    ->or
    ->equalTo('field', '')
->unnest();
```

### Dynamic Query Building

Build queries dynamically based on conditions:

```php
$select = $sql->select('products');

if ($categoryId) {
    $select->where(['categoryId' => $categoryId]);
}

if ($minPrice) {
    $select->where->greaterThanOrEqualTo('price', $minPrice);
}

if ($maxPrice) {
    $select->where->lessThanOrEqualTo('price', $maxPrice);
}

if ($searchTerm) {
    $select->where->nest()
        ->like('name', '%' . $searchTerm . '%')
        ->or
        ->like('description', '%' . $searchTerm . '%')
    ->unnest();
}

if ($sortBy) {
    $select->order($sortBy . ' ' . ($sortDirection ?? 'ASC'));
}

if ($limit) {
    $select->limit($limit);
    if ($offset) {
        $select->offset($offset);
    }
}
```

### Reusing Query Components

Create reusable query components for common patterns:

```php
function applyActiveFilter(Select $select): Select
{
    return $select->where([
        'status' => 'active',
        'deletedAt' => null,
    ]);
}

function applyPagination(Select $select, int $page, int $perPage): Select
{
    return $select
        ->limit($perPage)
        ->offset(($page - 1) * $perPage);
}

$select = $sql->select('users');
applyActiveFilter($select);
applyPagination($select, 2, 25);
```

## Troubleshooting and Common Issues

### Empty WHERE Protection Errors

If you encounter errors about empty WHERE clauses:

```php title="UPDATE Without WHERE Clause (Wrong)"
$update = $sql->update('users');
$update->set(['status' => 'inactive']);
// This will trigger empty WHERE protection!
```

Always include a WHERE clause for UPDATE and DELETE:

```php title="Adding WHERE Clause to UPDATE"
$update->where(['id' => 123]);
```

To intentionally update all rows (use with extreme caution):

```php title="Checking Empty WHERE Protection Status"
// Check the raw state to understand the protection status
$state = $update->getRawState();
$protected = $state['emptyWhereProtection'];
```

### Parameter Count Mismatch

When using Expression with placeholders:

```php title="Incorrect Parameter Count"
// WRONG - 3 placeholders but only 2 values
$expression = new Expression('CONCAT(?, ?, ?)', ['a', 'b']);
```

Ensure the number of `?` placeholders matches the number of parameters
provided, or you will receive a RuntimeException.

```php title="Correct Parameter Count"
// CORRECT
$expression = new Expression('CONCAT(?, ?, ?)', ['a', 'b', 'c']);
```

### Quote Character Issues

Different databases use different quote characters.
Let the platform handle quoting:

```php title="Proper Platform-Managed Quoting"
// CORRECT - let the platform handle quoting
$select->from('users');
```

Avoid manually quoting identifiers:

```php title="Avoid Manual Quoting"
// WRONG - don't manually quote
$select->from('"users"');
```

### Comparing Identifiers in Predicates

When comparing two identifiers (column to column), use the `Argument` class
to wrap the column names:

```php title="Column Comparison Using Argument Class"
use PhpDb\Sql\Argument;

$where->equalTo(
    Argument::identifier('table1.columnA'),
    Argument::identifier('table2.columnB')
);
```

This ensures both values are treated as column identifiers rather than
literal values, producing SQL like:

```sql
"table1"."columnA" = "table2"."columnB"
```

### Debugging SQL Output

To see the generated SQL for debugging:

```php
// Get the SQL string (DO NOT use for execution with user input!)
$sqlString = $sql->buildSqlString($select);
echo $sqlString;

// For debugging prepared statement parameters
$statement = $sql->prepareStatementForSqlObject($select);
// The statement object contains the SQL and parameter container
```

## Performance Considerations

### Use Prepared Statements

Always use `prepareStatementForSqlObject()` instead of
`buildSqlString()` for user input:

```php
$select->where(['username' => $userInput]);
$statement = $sql->prepareStatementForSqlObject($select);
```

This provides:

- Protection against SQL injection
- Better performance through query plan caching
- Proper type handling for parameters

### Limit Result Sets

Always use `limit()` for queries that may return large result sets:

```php
$select->limit(100);
```

For pagination, combine with `offset()`:

```php title="Pagination with Limit and Offset"
$select->limit(25)->offset(50);
```

### Select Only Required Columns

Instead of selecting all columns:

```php title="Selecting All Columns (Avoid)"
// Avoid - selects all columns
$select->from('users');
```

Specify only the columns you need:

```php title="Selecting Specific Columns"
// Better - only select what's needed
$select->from('users')->columns(['id', 'username', 'email']);
```

This reduces memory usage and network transfer.

### Avoid N+1 Query Problems

Use JOINs instead of multiple queries:

```php title="Using JOINs to Avoid N+1 Queries"
// WRONG - N+1 queries
foreach ($orders as $order) {
    // Additional query per order
    $customer = getCustomer($order['customerId']);
}

// CORRECT - single query with JOIN
$select->from('orders')
    ->join(
        'customers',
        'orders.customerId = customers.id',
        ['customerName' => 'name']
    )
    ->join(
        'products',
        'orders.productId = products.id',
        ['productName' => 'name']
    );
```

### Index-Friendly Queries

Structure WHERE clauses to use database indexes:

```php title="Index-Friendly WHERE Clause"
// Good - can use index on indexedColumn
$select->where->equalTo('indexedColumn', $value)
    ->greaterThan('date', '2024-01-01');
```

Avoid functions on indexed columns in WHERE:

```php title="Functions on Indexed Columns (Prevents Index Usage)"
// BAD - prevents index usage
$select->where(
    new Predicate\Expression('YEAR(createdAt) = ?', [2024])
);
```

Instead, use ranges:

```php title="Using Ranges for Index-Friendly Queries"
// GOOD - allows index usage
$select->where->between('createdAt', '2024-01-01', '2024-12-31');
```

## Complete Examples

```php title="Complex Reporting Query with Aggregation"
use PhpDb\Sql\Sql;
use PhpDb\Sql\Select;
use PhpDb\Sql\Expression;

$sql = new Sql($adapter);

$select = $sql->select('orders')
    ->columns([
        'customerId',
        'orderYear' => new Expression('YEAR(createdAt)'),
        'orderCount' => new Expression('COUNT(*)'),
        'totalRevenue' => new Expression('SUM(amount)'),
        'avgOrderValue' => new Expression('AVG(amount)'),
    ])
    ->join(
        'customers',
        'orders.customerId = customers.id',
        ['customerName' => 'name', 'customerTier' => 'tier'],
        Select::JOIN_LEFT
    )
    ->where(function ($where) {
        $where->nest()
            ->equalTo('orders.status', 'completed')
            ->or
            ->equalTo('orders.status', 'shipped')
        ->unnest();
        $where->between(
            'orders.createdAt',
            '2024-01-01',
            '2024-12-31'
        );
    })
    ->group(['customerId', new Expression('YEAR(createdAt)')])
    ->having(function ($having) {
        $having->greaterThan(new Expression('SUM(amount)'), 10000);
    })
    ->order(['totalRevenue DESC', 'orderYear DESC'])
    ->limit(100);

$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();
```

Produces:

```sql title="Generated SQL for Reporting Query"
SELECT orders.customerId,
       YEAR(createdAt) AS orderYear,
       COUNT(*) AS orderCount,
       SUM(amount) AS totalRevenue,
       AVG(amount) AS avgOrderValue,
       customers.name AS customerName,
       customers.tier AS customerTier
FROM orders
LEFT JOIN customers ON orders.customerId = customers.id
WHERE (orders.status = 'completed' OR orders.status = 'shipped')
  AND orders.createdAt BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY customerId, YEAR(createdAt)
HAVING SUM(amount) > 10000
ORDER BY totalRevenue DESC, orderYear DESC
LIMIT 100
```

```php title="Data Migration with INSERT SELECT"
$select = $sql->select('importedUsers')
    ->columns(['username', 'email', 'firstName', 'lastName'])
    ->where(['validated' => true])
    ->where->isNotNull('email');

$insert = $sql->insert('users');
$insert->columns(['username', 'email', 'firstName', 'lastName']);
$insert->select($select);

$statement = $sql->prepareStatementForSqlObject($insert);
$statement->execute();
```

Produces:

```sql title="Generated SQL for INSERT SELECT"
INSERT INTO users (username, email, firstName, lastName)
SELECT username, email, firstName, lastName
FROM importedUsers
WHERE validated = 1 AND email IS NOT NULL
```

```php title="Combining Multiple Result Sets"
use PhpDb\Sql\Combine;
use PhpDb\Sql\Literal;

$activeUsers = $sql->select('users')
    ->columns(['id', 'name', 'email', 'status' => new Literal('"active"')])
    ->where(['status' => 'active']);

$pendingUsers = $sql->select('userRegistrations')
    ->columns(['id', 'name', 'email', 'status' => new Literal('"pending"')])
    ->where(['verified' => false]);

$suspendedUsers = $sql->select('users')
    ->columns(['id', 'name', 'email', 'status' => new Literal('"suspended"')])
    ->where(['suspended' => true]);

$combine = new Combine();
$combine->union($activeUsers);
$combine->union($pendingUsers);
$combine->union($suspendedUsers);
$combine->alignColumns();

$statement = $sql->prepareStatementForSqlObject($combine);
$results = $statement->execute();
```

Produces:

```sql title="Generated SQL for UNION Query"
(SELECT id, name, email, "active" AS status
 FROM users WHERE status = 'active')
UNION
(SELECT id, name, email, "pending" AS status
 FROM userRegistrations WHERE verified = 0)
UNION
(SELECT id, name, email, "suspended" AS status
 FROM users WHERE suspended = 1)
```

```php title="Search with Full-Text and Filters"
use PhpDb\Sql\Predicate;

$select = $sql->select('products')
    ->columns([
        'id',
        'name',
        'description',
        'price',
        'relevance' => new Expression(
            'MATCH(name, description) AGAINST(?)',
            [$searchTerm]
        ),
    ])
    ->where(function ($where) use (
        $searchTerm,
        $categoryId,
        $minPrice,
        $maxPrice
    ) {
        // Full-text search
        $where->expression(
            'MATCH(name, description) AGAINST(? IN BOOLEAN MODE)',
            [$searchTerm]
        );

        // Category filter
        if ($categoryId) {
            $where->equalTo('categoryId', $categoryId);
        }

        // Price range
        if ($minPrice !== null && $maxPrice !== null) {
            $where->between('price', $minPrice, $maxPrice);
        } elseif ($minPrice !== null) {
            $where->greaterThanOrEqualTo('price', $minPrice);
        } elseif ($maxPrice !== null) {
            $where->lessThanOrEqualTo('price', $maxPrice);
        }

        // Only active products
        $where->equalTo('status', 'active');
    })
    ->order('relevance DESC')
    ->limit(50);
```

```php title="Batch Update with Transaction"
$connection = $adapter->getDriver()->getConnection();
$connection->beginTransaction();

try {
    // Deactivate old records
    $update = $sql->update('subscriptions');
    $update->set(['status' => 'expired']);
    $update->where->lessThan('expiresAt', new Expression('NOW()'));
    $update->where->equalTo('status', 'active');
    $sql->prepareStatementForSqlObject($update)->execute();

    // Archive processed orders
    $select = $sql->select('orders')
        ->where(['status' => 'completed'])
        ->where->lessThan(
            'completedAt',
            new Expression('DATE_SUB(NOW(), INTERVAL 1 YEAR)')
        );

    $insert = $sql->insert('orders_archive');
    $insert->select($select);
    $sql->prepareStatementForSqlObject($insert)->execute();

    // Delete archived orders from main table
    $delete = $sql->delete('orders');
    $delete->where(['status' => 'completed']);
    $delete->where->lessThan(
        'completedAt',
        new Expression('DATE_SUB(NOW(), INTERVAL 1 YEAR)')
    );
    $sql->prepareStatementForSqlObject($delete)->execute();

    $connection->commit();
} catch (\Exception $e) {
    $connection->rollback();
    throw $e;
}
```
