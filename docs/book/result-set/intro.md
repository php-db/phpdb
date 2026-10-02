# Result Sets

`PhpDb\ResultSet` abstracts iteration over database query results. Result
sets implement `ResultSetInterface` and are typically populated from
`ResultInterface` instances returned by query execution. Components use the
prototype pattern to clone and specialize result sets with specific data
sources.

`ResultSetInterface` is defined as follows:

## ResultSetInterface Definition

```php
use Countable;
use Traversable;

interface ResultSetInterface extends Traversable, Countable
{
    public function initialize(iterable $dataSource): ResultSetInterface;
    public function getFieldCount(): mixed;
    public function setRowPrototype(
        ArrayObject $rowPrototype
    ): ResultSetInterface;
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

```php title="AbstractResultSet API"
namespace PhpDb\ResultSet;

use Iterator;

abstract class AbstractResultSet implements ResultSetInterface
{
    protected const bool HOLDS_MAPPED_ROWS = false;

    public function initialize(iterable $dataSource): ResultSetInterface;
    public function getDataSource(): ?Iterator;
    public function getFieldCount(): int;

    public function buffer(): ResultSetInterface;
    public function isBuffered(): bool;

    public function next(): void;
    public function key(): int;
    public function valid(): bool;
    public function rewind(): void;

    public function count(): ?int;

    abstract protected function mapRow(mixed $row): mixed;
    protected function resetResolvedConfiguration(): void;
}
```

`ResultSetInterface` extends `Iterator` and `Countable` and adds `toArray()`. Each
concrete result set declares `current()` with its own row type and implements it by
returning `$this->currentRow()`, which serves the row from the buffer or the data source
and passes it through `mapRow()` to give it that shape. A custom result set implements
`current()`, `mapRow()` and `toArray()`. It can redeclare `HOLDS_MAPPED_ROWS` as `true`
to hand out the same row objects on every buffered pass, and override
`resetResolvedConfiguration()` to forget anything it cached when `initialize()` is given
a new data source.

## HydratingResultSet

`PhpDb\ResultSet\HydratingResultSet` is a more flexible `ResultSet` object
that allows the developer to choose an appropriate "hydration strategy" for
getting row data into a target object.  While iterating over results,
`HydratingResultSet` will take a prototype of a target object and clone it
once for each row. The `HydratingResultSet` will then hydrate that clone with
the row data.

The `HydratingResultSet` depends on
[laminas-hydrator](https://docs.laminas.dev/laminas-hydrator), which you will
need to install:

```bash title="Installing laminas-hydrator"
composer require laminas/laminas-hydrator
```

In the example below, rows from the database will be iterated, and during
iteration, `HydratingResultSet` will use the `Reflection` based hydrator to
inject the row data directly into the protected members of the cloned
`UserEntity` object:

```php title="Using HydratingResultSet with ReflectionHydrator"
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\HydratingResultSet;
use Laminas\Hydrator\Reflection as ReflectionHydrator;

$statement = $driver->createStatement('SELECT * FROM users');
$statement->prepare();
$result = $statement->execute();

if ($result instanceof ResultInterface && $result->isQueryResult()) {
    $resultSet = new HydratingResultSet(
        new ReflectionHydrator(),
        new UserEntity()
    );
    $resultSet->initialize($result);

    foreach ($resultSet as $user) {
        printf("%s %s\n", $user->getFirstName(), $user->getLastName());
    }
}
```

For more information, see the
[laminas-hydrator](https://docs.laminas.dev/laminas-hydrator/)
documentation to get a better sense of the different strategies that can be
employed in order to populate a target object.

## ObjectResultSet

`PhpDb\ResultSet\ObjectResultSet` is for fetch modes that yield objects, such as
`PDO::FETCH_OBJ` and `PDO::FETCH_LAZY`. Rows leave it as the very instances the driver
produced: nothing is cloned, hydrated or reshaped.

```php title="Using ObjectResultSet with PDO::FETCH_OBJ"
use PDO;
use PhpDb\ResultSet\ObjectResultSet;

$result = $statement->execute();
$result->setFetchMode(PDO::FETCH_OBJ);

$resultSet = new ObjectResultSet();
$resultSet->initialize($result);

foreach ($resultSet as $user) {
    printf("%s %s\n", $user->first_name, $user->last_name);
}
```

`toArray()` reads each row's values, either by traversing it or by reading its public
properties. A row that exposes neither — a `PDORow` from `PDO::FETCH_LAZY`, for
instance — throws rather than yielding an empty array, so iterate such a result set
instead of calling `toArray()` on it.

## Choosing a Result Set

A result set fills its rows from row data, meaning an `array` or an `ArrayObject`. It
will not transform a row the driver handed it into some other shape, so the fetch mode
and the result set have to agree; where they do not, the row is refused with a
`PhpDb\ResultSet\Exception\ValueError` naming both types.

| Result set | Rows arrive as | Rows leave as |
|---|---|---|
| `ResultSet` | row data, or an `ArrayObject` | a filled `ArrayObject` prototype, an array, or that same `ArrayObject` |
| `ArrayResultSet` | row data | an array |
| `ObjectResultSet` | any object | that same object |
| `HydratingResultSet` | row data | the hydrated row prototype |
| `RowPrototypeResultSet` | row data, or a `RowPrototypeInterface` | a populated `RowPrototypeInterface` |

With PDO that means `FETCH_ASSOC` and `FETCH_NAMED` suit every row-data set.
`FETCH_NUM`, `FETCH_BOTH` and `FETCH_KEY_PAIR` give rows with integer keys, which
`ResultSet`, `ArrayResultSet` and `RowPrototypeResultSet` accept but a hydrator cannot
map to properties, so `HydratingResultSet` fails on them with a `TypeError` from
laminas-hydrator. `FETCH_OBJ` and `FETCH_LAZY` need `ObjectResultSet`. `FETCH_BOUND`
yields only a success flag and binds its columns by reference, so no result set accepts
its rows; read the bound variables instead.

## Data Source Types

The `initialize()` method accepts arrays, `Iterator`, `IteratorAggregate`,
or `ResultInterface`:

```php
// Arrays (auto-buffered, allows multiple iterations)
$resultSet->initialize([['id' => 1], ['id' => 2]]);

// Iterator/IteratorAggregate
$resultSet->initialize(new ArrayIterator($data));

// ResultInterface (most common - from query execution)
$resultSet->initialize($statement->execute());
```
