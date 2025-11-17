# Result Sets

`PhpDb\ResultSet` is a sub-component of laminas-db for abstracting the iteration
of results returned from queries producing rowsets. While data sources for this
can be anything that is iterable, generally these will be populated from
`PhpDb\Adapter\Driver\ResultInterface` instances.

Result sets must implement the `PhpDb\ResultSet\ResultSetInterface`, and all
sub-components of laminas-db that return a result set as part of their API will
assume an instance of a `ResultSetInterface` should be returned. In most cases,
the prototype pattern will be used by consuming object to clone a prototype of
a `ResultSet` and return a specialized `ResultSet` with a specific data source
injected. `ResultSetInterface` is defined as follows:

```php
use Countable;
use Traversable;

interface ResultSetInterface extends Traversable, Countable
{
    public function initialize(mixed $dataSource) : void;
    public function getFieldCount() : int;
}
```

## Quick Start

`PhpDb\ResultSet\ResultSet` is the most basic form of a `ResultSet` object
that will expose each row as either an `ArrayObject`-like object or an array of
row data. By default, `PhpDb\Adapter\Adapter` will use a prototypical
`PhpDb\ResultSet\ResultSet` object for iterating when using the
`PhpDb\Adapter\Adapter::query()` method.

### Example Data

Throughout this documentation, we'll use this sample dataset:

```php
$sampleData = [
    ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Johnson', 'email' => 'alice@example.com'],
    ['id' => 2, 'first_name' => 'Bob', 'last_name' => 'Smith', 'email' => 'bob@example.com'],
    ['id' => 3, 'first_name' => 'Charlie', 'last_name' => 'Brown', 'email' => 'charlie@example.com'],
    ['id' => 4, 'first_name' => 'Diana', 'last_name' => 'Prince', 'email' => 'diana@example.com'],
];
```

And this UserEntity class for hydration examples:

```php
class UserEntity
{
    protected int $id;
    protected string $first_name;
    protected string $last_name;
    protected string $email;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setFirstName(string $firstName): void
    {
        $this->first_name = $firstName;
    }

    public function setLastName(string $lastName): void
    {
        $this->last_name = $lastName;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
```

### Basic Usage

The following is an example workflow similar to what one might find inside
`PhpDb\Adapter\Adapter::query()`:

```php
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\ResultSet;

$statement = $driver->createStatement('SELECT * FROM users');
$statement->prepare();
$result = $statement->execute($parameters);

if ($result instanceof ResultInterface && $result->isQueryResult()) {
    $resultSet = new ResultSet();
    $resultSet->initialize($result);

    foreach ($resultSet as $row) {
        printf("User: %s %s\n", $row->first_name, $row->last_name);
    }
}
```

## ResultSet Classes

### AbstractResultSet

For most purposes, either an instance of `PhpDb\ResultSet\ResultSet` or a
derivative of `PhpDb\ResultSet\AbstractResultSet` will be used. The
implementation of the `AbstractResultSet` offers the following core
functionality:

```php
namespace PhpDb\ResultSet;

use Iterator;
use IteratorAggregate;
use PhpDb\Adapter\Driver\ResultInterface;

abstract class AbstractResultSet implements Iterator, ResultSetInterface
{
    public function initialize(array|Iterator|IteratorAggregate|ResultInterface $dataSource): ResultSetInterface;
    public function getDataSource(): array|Iterator|IteratorAggregate|ResultInterface;
    public function getFieldCount(): int;

    public function buffer(): ResultSetInterface;
    public function isBuffered(): bool;

    public function next(): void;
    public function key(): int;
    public function current(): mixed;
    public function valid(): bool;
    public function rewind(): void;

    public function count(): int;

    public function toArray(): array;
}
```

## Laminas\\Db\\ResultSet\\HydratingResultSet

`PhpDb\ResultSet\HydratingResultSet` is a more flexible `ResultSet` object
that allows the developer to choose an appropriate "hydration strategy" for
getting row data into a target object.  While iterating over results,
`HydratingResultSet` will take a prototype of a target object and clone it once
for each row. The `HydratingResultSet` will then hydrate that clone with the
row data.

The `HydratingResultSet` depends on
[laminas-hydrator](https://docs.laminas.dev/laminas-hydrator), which you will
need to install:

```bash
composer require laminas/laminas-hydrator
```

In the example below, rows from the database will be iterated, and during
iteration, `HydratingResultSet` will use the `Reflection` based hydrator to
inject the row data directly into the protected members of the cloned
`UserEntity` object:

```php
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\HydratingResultSet;
use Laminas\Hydrator\Reflection as ReflectionHydrator;

$statement = $driver->createStatement('SELECT * FROM users');
$statement->prepare();
$result = $statement->execute();

if ($result instanceof ResultInterface && $result->isQueryResult()) {
    $resultSet = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());
    $resultSet->initialize($result);

    foreach ($resultSet as $user) {
        printf("%s %s\n", $user->getFirstName(), $user->getLastName());
    }
}
```

For more information, see the [laminas-hydrator](https://docs.laminas.dev/laminas-hydrator/)
documentation to get a better sense of the different strategies that can be
employed in order to populate a target object.

## ResultSet API Reference

### ResultSet Class

The `ResultSet` class extends `AbstractResultSet` and provides row data as either
`ArrayObject` instances or plain arrays.

```php
namespace PhpDb\ResultSet;

use ArrayObject;

class ResultSet extends AbstractResultSet
{
    public const TYPE_ARRAYOBJECT = 'arrayobject';
    public const TYPE_ARRAY = 'array';

    public function __construct(
        string $returnType = self::TYPE_ARRAYOBJECT,
        ?ArrayObject $arrayObjectPrototype = null
    );

    public function setArrayObjectPrototype(ArrayObject $arrayObjectPrototype): static;
    public function getArrayObjectPrototype(): ArrayObject;
    public function getReturnType(): string;
}
```

#### Constructor Parameters

**`$returnType`** - Controls how rows are returned:
- `ResultSet::TYPE_ARRAYOBJECT` (default) - Returns rows as ArrayObject instances
- `ResultSet::TYPE_ARRAY` - Returns rows as plain PHP arrays

**`$arrayObjectPrototype`** - Custom ArrayObject prototype for row objects (only used with TYPE_ARRAYOBJECT)

#### Return Type Modes

**ArrayObject Mode** (default):

```php
$resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    printf("ID: %d, Name: %s\n", $row->id, $row->name);
    printf("Array access also works: %s\n", $row['name']);
}
```

**Array Mode:**

```php
$resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    printf("ID: %d, Name: %s\n", $row['id'], $row['name']);
}
```

The array mode is more memory efficient for large result sets.

### HydratingResultSet Class

Complete API for `HydratingResultSet`:

```php
namespace PhpDb\ResultSet;

use Laminas\Hydrator\HydratorInterface;

class HydratingResultSet extends AbstractResultSet
{
    public function __construct(
        ?HydratorInterface $hydrator = null,
        ?object $objectPrototype = null
    );

    public function setHydrator(HydratorInterface $hydrator): static;
    public function getHydrator(): HydratorInterface;

    public function setObjectPrototype(object $objectPrototype): static;
    public function getObjectPrototype(): ?object;

    public function current(): ?object;
    public function toArray(): array;
}
```

#### Constructor Defaults

If no hydrator is provided, `ArraySerializableHydrator` is used by default:

```php
$resultSet = new HydratingResultSet();
```

If no object prototype is provided, `ArrayObject` is used:

```php
$resultSet = new HydratingResultSet(new ReflectionHydrator());
```

#### Runtime Hydrator Changes

You can change the hydration strategy at runtime:

```php
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\ReflectionHydrator;

$resultSet = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());
$resultSet->initialize($result);

foreach ($resultSet as $user) {
    printf("%s %s\n", $user->getFirstName(), $user->getLastName());
}

$resultSet->setHydrator(new ClassMethodsHydrator());
```

## Buffer Management

Result sets can be buffered to allow multiple iterations and rewinding. By default,
result sets are not buffered until explicitly requested.

### buffer() Method

Forces the result set to buffer all rows into memory:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);
$resultSet->buffer();

foreach ($resultSet as $row) {
    printf("%s\n", $row->name);
}

$resultSet->rewind();

foreach ($resultSet as $row) {
    printf("%s (second iteration)\n", $row->name);
}
```

**Important:** Calling `buffer()` after iteration has started throws `RuntimeException`:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    break;
}

$resultSet->buffer();
```

Throws:

```
RuntimeException: Buffering must be enabled before iteration is started
```

### isBuffered() Method

Checks if the result set is currently buffered:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);

var_dump($resultSet->isBuffered());

$resultSet->buffer();

var_dump($resultSet->isBuffered());
```

Outputs:

```
bool(false)
bool(true)
```

### Automatic Buffering

Arrays and certain data sources are automatically buffered:

```php
$resultSet = new ResultSet();
$resultSet->initialize([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
]);

var_dump($resultSet->isBuffered());
```

Outputs:

```
bool(true)
```

## ArrayObject Access Patterns

When using `TYPE_ARRAYOBJECT` mode (default), rows support both property and array access:

```php
$resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    printf("Property access: %s\n", $row->username);
    printf("Array access: %s\n", $row['username']);

    if (isset($row->email)) {
        printf("Email: %s\n", $row->email);
    }

    if (isset($row['phone'])) {
        printf("Phone: %s\n", $row['phone']);
    }
}
```

This flexibility comes from `ArrayObject` being constructed with the
`ArrayObject::ARRAY_AS_PROPS` flag.

### Custom ArrayObject Prototypes

You can provide a custom ArrayObject subclass:

```php
class CustomRow extends ArrayObject
{
    public function getFullName(): string
    {
        return $this['first_name'] . ' ' . $this['last_name'];
    }
}

$prototype = new CustomRow([], ArrayObject::ARRAY_AS_PROPS);
$resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $prototype);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    printf("Full name: %s\n", $row->getFullName());
}
```

## The Prototype Pattern

Result sets use the prototype pattern for efficiency and state isolation.

### How It Works

When `Adapter::query()` or `TableGateway::select()` execute, they:

1. Clone the prototype ResultSet
2. Initialize the clone with fresh data
3. Return the clone

This ensures each query gets an isolated ResultSet instance:

```php
$resultSet1 = $adapter->query('SELECT * FROM users');
$resultSet2 = $adapter->query('SELECT * FROM posts');
```

Both `$resultSet1` and `$resultSet2` are independent clones with their own state.

### Customizing the Prototype

You can provide a custom ResultSet prototype to the Adapter:

```php
use PhpDb\Adapter\Adapter;
use PhpDb\ResultSet\ResultSet;

$customResultSet = new ResultSet(ResultSet::TYPE_ARRAY);

$adapter = new Adapter([
    'driver' => 'Pdo_Mysql',
    'database' => 'mydb',
], $customResultSet);

$resultSet = $adapter->query('SELECT * FROM users');
```

Now all queries return plain arrays instead of ArrayObject instances.

### TableGateway Prototype

TableGateway also uses a ResultSet prototype:

```php
use PhpDb\ResultSet\HydratingResultSet;
use PhpDb\TableGateway\TableGateway;
use Laminas\Hydrator\ReflectionHydrator;

$prototype = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());

$userTable = new TableGateway('users', $adapter, null, $prototype);

$users = $userTable->select(['status' => 'active']);

foreach ($users as $user) {
    printf("%s: %s\n", $user->getId(), $user->getEmail());
}
```

## Data Source Types

The `initialize()` method accepts multiple data source types:

### Arrays

```php
$resultSet = new ResultSet();
$resultSet->initialize([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
]);
```

Arrays are automatically buffered and allow multiple iterations.

### Iterator

```php
$resultSet = new ResultSet();
$resultSet->initialize(new ArrayIterator($data));
```

### IteratorAggregate

```php
$resultSet = new ResultSet();
$resultSet->initialize($iteratorAggregate);
```

### ResultInterface (Driver Result)

```php
$result = $statement->execute();
$resultSet = new ResultSet();
$resultSet->initialize($result);
```

This is the most common use case when working with database queries.

## Performance and Memory Management

### Buffered vs Unbuffered

**Unbuffered (default):**
- Memory usage: O(1) per row
- Supports single iteration only
- Cannot rewind without buffering
- Ideal for large result sets processed once

**Buffered:**
- Memory usage: O(n) for all rows
- Supports multiple iterations
- Allows rewinding
- Required for `count()` on unbuffered sources
- Required for `toArray()`

### When to Buffer

Buffer when you need to:

```php
$resultSet->buffer();

$count = $resultSet->count();

foreach ($resultSet as $row) {
    processRow($row);
}

$resultSet->rewind();

foreach ($resultSet as $row) {
    processRowAgain($row);
}
```

Don't buffer for single-pass large result sets:

```php
$resultSet = $adapter->query('SELECT * FROM huge_table');

foreach ($resultSet as $row) {
    processRow($row);
}
```

### Memory Efficiency Comparison

```php
$arrayMode = new ResultSet(ResultSet::TYPE_ARRAY);
$arrayMode->initialize($result);

$arrayObjectMode = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);
$arrayObjectMode->initialize($result);
```

`TYPE_ARRAY` uses less memory per row than `TYPE_ARRAYOBJECT` because it avoids
object overhead.

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

**ArrayObject without exchangeArray() method:**

```php
try {
    $invalidPrototype = new ArrayObject();
    unset($invalidPrototype->exchangeArray);
    $resultSet->setArrayObjectPrototype($invalidPrototype);
} catch (InvalidArgumentException $e) {
    printf("Error: %s\n", $e->getMessage());
}
```

**Non-object passed to HydratingResultSet:**

```php
try {
    $resultSet->setObjectPrototype('not an object');
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

## Advanced Usage

### Multiple Hydrators

Switch hydrators based on context:

```php
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\ReflectionHydrator;

$resultSet = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());

if ($includePrivateProps) {
    $resultSet->setHydrator(new ReflectionHydrator());
} else {
    $resultSet->setHydrator(new ClassMethodsHydrator());
}
```

### Converting to Arrays

Extract all rows as arrays:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);

$allRows = $resultSet->toArray();

printf("Found %d rows\n", count($allRows));
```

With HydratingResultSet, `toArray()` uses the hydrator's extractor:

```php
$resultSet = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());
$resultSet->initialize($result);

$allRows = $resultSet->toArray();
```

Each row is extracted back to an array using the hydrator's `extract()` method.

### Accessing Current Row

Get the current row without iteration:

```php
$resultSet = new ResultSet();
$resultSet->initialize($result);

$firstRow = $resultSet->current();
```

This returns the first row without advancing the iterator.

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

## Troubleshooting

### Cannot Rewind After Iteration

**Problem:** Trying to iterate twice fails

**Solution:** Buffer the result set before first iteration

```php
$resultSet->buffer();

foreach ($resultSet as $row) {
    processRow($row);
}

$resultSet->rewind();

foreach ($resultSet as $row) {
    processRowAgain($row);
}
```

### Out of Memory Errors

**Problem:** Large result sets cause memory exhaustion

**Solution:** Use TYPE_ARRAY mode and avoid buffering

```php
$resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    processRow($row);
}
```

### Property Access Not Working

**Problem:** `$row->column_name` returns null

**Solution:** Ensure using TYPE_ARRAYOBJECT mode (default)

```php
$resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);
```

Or use array access instead:

```php
$value = $row['column_name'];
```

### Hydration Failures

**Problem:** Object properties not populated

**Solution:** Ensure hydrator matches object structure

```php
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\ReflectionHydrator;

$resultSet = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());
```

Use `ReflectionHydrator` for protected/private properties, `ClassMethodsHydrator`
for public setters.

### Invalid Data Source Exception

**Problem:** `InvalidArgumentException` on initialize()

**Solution:** Ensure data source is array, Iterator, IteratorAggregate, or ResultInterface

```php
$resultSet->initialize($validDataSource);
```
