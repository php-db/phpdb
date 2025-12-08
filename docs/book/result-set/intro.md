# Result Sets

`PhpDb\ResultSet` abstracts iteration over database query results. Result sets implement `ResultSetInterface` and are typically populated from `ResultInterface` instances returned by query execution. Components use the prototype pattern to clone and specialize result sets with specific data sources.

`ResultSetInterface` is defined as follows:

### ResultSetInterface Definition

```php
use Countable;
use Traversable;

interface ResultSetInterface extends Traversable, Countable
{
    public function initialize(iterable $dataSource): ResultSetInterface;
    public function getFieldCount(): mixed;
    public function setRowPrototype(ArrayObject $rowPrototype): ResultSetInterface;
    public function getRowPrototype(): ?object;
}
```

## Quick Start

`PhpDb\ResultSet\ResultSet` is the most basic form of a `ResultSet` object
that will expose each row as either an `ArrayObject`-like object or an array of
row data. By default, `PhpDb\Adapter\Adapter` will use a prototypical
`PhpDb\ResultSet\ResultSet` object for iterating when using the
`PhpDb\Adapter\Adapter::query()` method.

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

### AbstractResultSet API

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

## HydratingResultSet

`PhpDb\ResultSet\HydratingResultSet` is a more flexible `ResultSet` object
that allows the developer to choose an appropriate "hydration strategy" for
getting row data into a target object.  While iterating over results,
`HydratingResultSet` will take a prototype of a target object and clone it once
for each row. The `HydratingResultSet` will then hydrate that clone with the
row data.

The `HydratingResultSet` depends on
[laminas-hydrator](https://docs.laminas.dev/laminas-hydrator), which you will
need to install:

### Installing laminas-hydrator

```bash
composer require laminas/laminas-hydrator
```

In the example below, rows from the database will be iterated, and during
iteration, `HydratingResultSet` will use the `Reflection` based hydrator to
inject the row data directly into the protected members of the cloned
`UserEntity` object:

### Using HydratingResultSet with ReflectionHydrator

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

## Data Source Types

The `initialize()` method accepts arrays, `Iterator`, `IteratorAggregate`, or `ResultInterface`:

```php
// Arrays (auto-buffered, allows multiple iterations)
$resultSet->initialize([['id' => 1], ['id' => 2]]);

// Iterator/IteratorAggregate
$resultSet->initialize(new ArrayIterator($data));

// ResultInterface (most common - from query execution)
$resultSet->initialize($statement->execute());
```