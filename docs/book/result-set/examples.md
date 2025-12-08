# Result Set Examples and Troubleshooting

## Common Patterns and Best Practices

### Processing Large Result Sets

For memory efficiency with large result sets:

```php
$resultSet = $adapter->query('SELECT * FROM large_table');

foreach ($resultSet as $row) {
    processRow($row);

    if ($someCondition) {
        break;
    }
}
```

Don't buffer or call `toArray()` on large datasets.

### Reusable Hydrated Entities

Create a reusable ResultSet prototype:

```php
function createUserResultSet(): HydratingResultSet
{
    return new HydratingResultSet(
        new ReflectionHydrator(),
        new UserEntity()
    );
}

$users = $userTable->select(['status' => 'active']);

foreach ($users as $user) {
    printf("%s\n", $user->getEmail());
}
```

### Counting Results

For accurate counts with unbuffered result sets, buffer first:

```php
$resultSet = $adapter->query('SELECT * FROM users');
$resultSet->buffer();

printf("Total users: %d\n", $resultSet->count());

foreach ($resultSet as $user) {
    printf("User: %s\n", $user->username);
}
```

### Checking for Empty Results

```php
$resultSet = $adapter->query('SELECT * FROM users WHERE id = ?', [999]);

if ($resultSet->count() === 0) {
    printf("No users found\n");
}
```

### Multiple Iterations

When you need to iterate over results multiple times:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);
$resultSet->buffer(); // Must buffer before first iteration

// First pass - collect IDs
$ids = [];
foreach ($resultSet as $row) {
    $ids[] = $row->id;
}

// Rewind and iterate again
$resultSet->rewind();

// Second pass - process data
foreach ($resultSet as $row) {
    processRow($row);
}
```

### Conditional Hydration

Choose hydration based on query type:

```php
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\ReflectionHydrator;

function getResultSet(string $entityClass, bool $useReflection = true): HydratingResultSet
{
    $hydrator = $useReflection
        ? new ReflectionHydrator()
        : new ClassMethodsHydrator();

    return new HydratingResultSet($hydrator, new $entityClass());
}

$users = $userTable->select(['status' => 'active']);
```

### Working with Joins

When joining tables, use array mode or custom ArrayObject:

```php
use PhpDb\ResultSet\ResultSetReturnType;

$resultSet = new ResultSet(ResultSetReturnType::Array);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    $userId = $row['user_id'];
    $userName = $row['user_name'];
    $orderTotal = $row['order_total'];

    printf("User %s has order total: $%.2f\n", $userName, $orderTotal);
}
```

### Transforming Results

Transform rows during iteration:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);

$users = [];
foreach ($resultSet as $row) {
    $users[] = [
        'fullName' => $row->first_name . ' ' . $row->last_name,
        'email' => strtolower($row->email),
        'isActive' => (bool) $row->status,
    ];
}
```

## Error Handling and Exceptions

Result sets throw exceptions from the `PhpDb\ResultSet\Exception` namespace.

### InvalidArgumentException

**Invalid data source type:**

```php
use PhpDb\ResultSet\Exception\InvalidArgumentException;

try {
    $resultSet->initialize('invalid');
} catch (InvalidArgumentException $e) {
    printf("Error: %s\n", $e->getMessage());
}
```

**Invalid row prototype:**

```php
try {
    $invalidPrototype = new ArrayObject();
    unset($invalidPrototype->exchangeArray);
    $resultSet->setRowPrototype($invalidPrototype);
} catch (InvalidArgumentException $e) {
    printf("Error: %s\n", $e->getMessage());
}
```

**Non-object passed to HydratingResultSet:**

```php
try {
    $resultSet->setRowPrototype('not an object');
} catch (InvalidArgumentException $e) {
    printf("Error: %s\n", $e->getMessage());
}
```

### RuntimeException

**Buffering after iteration started:**

```php
use PhpDb\ResultSet\Exception\RuntimeException;

$resultSet = new ResultSet();
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    break;
}

try {
    $resultSet->buffer();
} catch (RuntimeException $e) {
    printf("Error: %s\n", $e->getMessage());
}
```

**toArray() on non-castable rows:**

```php
try {
    $resultSet->toArray();
} catch (RuntimeException $e) {
    printf("Error: Could not convert row to array\n");
}
```

## Troubleshooting

### Property Access Not Working

`$row->column_name` returns null? Ensure using ArrayObject mode (default), or use array access: `$row['column_name']`.

### Hydration Failures

Object properties not populated? Match hydrator to object structure:

- `ReflectionHydrator` for protected/private properties
- `ClassMethodsHydrator` for public setters

### Rows Are Empty Objects

Column names must match property names or setter methods:

```php
// Database columns: first_name, last_name
class UserEntity
{
    protected string $first_name; // Matches column name
    public function setFirstName($value) {} // For ClassMethodsHydrator
}
```

### toArray() Issues

Ensure the result set is buffered first: `$resultSet->buffer()`. For `HydratingResultSet`, the hydrator must have an `extract()` method (e.g., `ReflectionHydrator`).

## Performance Tips

### Use Array Mode for Read-Only Data

When you don't need object features:

```php
use PhpDb\ResultSet\ResultSetReturnType;

$resultSet = new ResultSet(ResultSetReturnType::Array);
$resultSet->initialize($result);
```

### Avoid Buffering Large Result Sets

Process rows one at a time:

```php
$resultSet = $adapter->query('SELECT * FROM million_rows');

foreach ($resultSet as $row) {
    // Process each row immediately
    yield processRow($row);
}
```

### Use Generators for Transformation

```php
function transformUsers(ResultSetInterface $resultSet): Generator
{
    foreach ($resultSet as $row) {
        yield [
            'name' => $row->first_name . ' ' . $row->last_name,
            'email' => $row->email,
        ];
    }
}

$users = transformUsers($resultSet);
foreach ($users as $user) {
    printf("%s: %s\n", $user['name'], $user['email']);
}
```

### Limit Queries When Possible

Reduce data at the database level:

```php
$resultSet = $adapter->query('SELECT id, name FROM users WHERE active = 1 LIMIT 100');
```

### Profile Memory Usage

Monitor memory with large result sets:

```php
$startMemory = memory_get_usage();

foreach ($resultSet as $row) {
    processRow($row);
}

$endMemory = memory_get_usage();
printf("Memory used: %d bytes\n", $endMemory - $startMemory);
```
