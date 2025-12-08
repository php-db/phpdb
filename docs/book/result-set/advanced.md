# Result Set API and Advanced Features

## ResultSet API Reference

### ResultSet Class

The `ResultSet` class extends `AbstractResultSet` and provides row data as either
`ArrayObject` instances or plain arrays.

```php title="ResultSet Class Definition"
namespace PhpDb\ResultSet;

use ArrayObject;

class ResultSet extends AbstractResultSet
{
    public function __construct(
        ResultSetReturnType $returnType = ResultSetReturnType::ArrayObject,
        ?ArrayObject $rowPrototype = null
    );

    public function setRowPrototype(ArrayObject $rowPrototype): ResultSetInterface;
    public function getRowPrototype(): ArrayObject;
    public function getReturnType(): ResultSetReturnType;
}
```

### ResultSetReturnType Enum

The `ResultSetReturnType` enum provides type-safe return type configuration:

```php title="ResultSetReturnType Definition"
namespace PhpDb\ResultSet;

enum ResultSetReturnType: string
{
    case ArrayObject = 'arrayobject';
    case Array = 'array';
}
```

```php title="Using ResultSetReturnType"
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetReturnType;

$resultSet = new ResultSet(ResultSetReturnType::ArrayObject);
$resultSet = new ResultSet(ResultSetReturnType::Array);
```

#### Constructor Parameters

**`$returnType`** - Controls how rows are returned:

- `ResultSetReturnType::ArrayObject` (default) - Returns rows as ArrayObject instances
- `ResultSetReturnType::Array` - Returns rows as plain PHP arrays

**`$rowPrototype`** - Custom ArrayObject prototype for row objects (only used with ArrayObject mode)

#### Return Type Modes

**ArrayObject Mode** (default):

```php title="ArrayObject Mode Example"
$resultSet = new ResultSet(ResultSetReturnType::ArrayObject);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    printf("ID: %d, Name: %s\n", $row->id, $row->name);
    printf("Array access also works: %s\n", $row['name']);
}
```

**Array Mode:**

```php title="Array Mode Example"
$resultSet = new ResultSet(ResultSetReturnType::Array);
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    printf("ID: %d, Name: %s\n", $row['id'], $row['name']);
}
```

The array mode is more memory efficient for large result sets.

### HydratingResultSet Class

Complete API for `HydratingResultSet`:

```php title="HydratingResultSet Class Definition"
namespace PhpDb\ResultSet;

use Laminas\Hydrator\HydratorInterface;

class HydratingResultSet extends AbstractResultSet
{
    public function __construct(
        ?HydratorInterface $hydrator = null,
        ?object $rowPrototype = null
    );

    public function setHydrator(HydratorInterface $hydrator): ResultSetInterface;
    public function getHydrator(): HydratorInterface;

    public function setRowPrototype(object $rowPrototype): ResultSetInterface;
    public function getRowPrototype(): object;

    public function current(): ?object;
    public function toArray(): array;
}
```

#### Constructor Defaults

If no hydrator is provided, `ArraySerializableHydrator` is used by default:

```php title="Default Hydrator"
$resultSet = new HydratingResultSet();
```

If no object prototype is provided, `ArrayObject` is used:

```php title="Default Object Prototype"
$resultSet = new HydratingResultSet(new ReflectionHydrator());
```

#### Runtime Hydrator Changes

You can change the hydration strategy at runtime:

```php title="Changing Hydrator at Runtime"
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

```php title="Buffering for Multiple Iterations"
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

```php title="Buffer After Iteration Error"
$resultSet = new ResultSet();
$resultSet->initialize($result);

foreach ($resultSet as $row) {
    break;
}

$resultSet->buffer();
```

Throws:

```text
RuntimeException: Buffering must be enabled before iteration is started
```

### isBuffered() Method

Checks if the result set is currently buffered:

```php title="Checking Buffer Status"
$resultSet = new ResultSet();
$resultSet->initialize($result);

var_dump($resultSet->isBuffered());

$resultSet->buffer();

var_dump($resultSet->isBuffered());
```

Outputs:

```text
bool(false)
bool(true)
```

### Automatic Buffering

Arrays and certain data sources are automatically buffered:

```php title="Array Data Source Auto-Buffering"
$resultSet = new ResultSet();
$resultSet->initialize([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
]);

var_dump($resultSet->isBuffered());
```

Outputs:

```text
bool(true)
```

## ArrayObject Access Patterns

When using ArrayObject mode (default), rows support both property and array access:

```php title="Property and Array Access"
$resultSet = new ResultSet(ResultSetReturnType::ArrayObject);
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

```php title="Custom Row Class with Helper Methods"
class CustomRow extends ArrayObject
{
    public function getFullName(): string
    {
        return $this['first_name'] . ' ' . $this['last_name'];
    }
}

$prototype = new CustomRow([], ArrayObject::ARRAY_AS_PROPS);
$resultSet = new ResultSet(ResultSetReturnType::ArrayObject, $prototype);
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

```php title="Independent Query Results"
$resultSet1 = $adapter->query('SELECT * FROM users');
$resultSet2 = $adapter->query('SELECT * FROM posts');
```

Both `$resultSet1` and `$resultSet2` are independent clones with their own state.

### Customizing the Prototype

You can provide a custom ResultSet prototype to the Adapter:

```php title="Custom Adapter Prototype"
use PhpDb\Adapter\Adapter;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetReturnType;

$customResultSet = new ResultSet(ResultSetReturnType::Array);

$adapter = new Adapter($driver, $platform, $customResultSet);

$resultSet = $adapter->query('SELECT * FROM users');
```

Now all queries return plain arrays instead of ArrayObject instances.

### TableGateway Prototype

TableGateway also uses a ResultSet prototype:

```php title="TableGateway with HydratingResultSet"
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

```php title="Buffering for Count and Multiple Passes"
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

```php title="Streaming Large Result Sets"
$resultSet = $adapter->query('SELECT * FROM huge_table');

foreach ($resultSet as $row) {
    processRow($row);
}
```

### Memory Efficiency Comparison

```php title="Comparing Array vs ArrayObject Mode"
use PhpDb\ResultSet\ResultSetReturnType;

$arrayMode = new ResultSet(ResultSetReturnType::Array);
$arrayMode->initialize($result);

$arrayObjectMode = new ResultSet(ResultSetReturnType::ArrayObject);
$arrayObjectMode->initialize($result);
```

Array mode uses less memory per row than ArrayObject mode because it avoids
object overhead.

## Advanced Usage

### Multiple Hydrators

Switch hydrators based on context:

```php title="Conditional Hydrator Selection"
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

```php title="Using toArray()"
$resultSet = new ResultSet();
$resultSet->initialize($result);

$allRows = $resultSet->toArray();

printf("Found %d rows\n", count($allRows));
```

With HydratingResultSet, `toArray()` uses the hydrator's extractor:

```php title="toArray() with HydratingResultSet"
$resultSet = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());
$resultSet->initialize($result);

$allRows = $resultSet->toArray();
```

Each row is extracted back to an array using the hydrator's `extract()` method.

### Accessing Current Row

Get the current row without iteration:

```php title="Getting First Row with current()"
$resultSet = new ResultSet();
$resultSet->initialize($result);

$firstRow = $resultSet->current();
```

This returns the first row without advancing the iterator.
