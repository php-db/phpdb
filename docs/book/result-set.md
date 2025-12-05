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

## Quick start

`PhpDb\ResultSet\ResultSet` is the most basic form of a `ResultSet` object
that will expose each row as either an `ArrayObject`-like object or an array of
row data. By default, `PhpDb\Adapter\Adapter` will use a prototypical
`PhpDb\ResultSet\ResultSet` object for iterating when using the
`PhpDb\Adapter\Adapter::query()` method.

The following is an example workflow similar to what one might find inside
`PhpDb\Adapter\Adapter::query()`:

```php
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\ResultSet;

$statement = $driver->createStatement('SELECT * FROM users');
$statement->prepare();
$result = $statement->execute($parameters);

if ($result instanceof ResultInterface && $result->isQueryResult()) {
    $resultSet = new ResultSet;
    $resultSet->initialize($result);

    foreach ($resultSet as $row) {
        echo $row->my_column . PHP_EOL;
    }
}
```

## Laminas\\Db\\ResultSet\\ResultSet and Laminas\\Db\\ResultSet\\AbstractResultSet

For most purposes, either an instance of `PhpDb\ResultSet\ResultSet` or a
derivative of `PhpDb\ResultSet\AbstractResultSet` will be used. The
implementation of the `AbstractResultSet` offers the following core
functionality:

```php
namespace PhpDb\ResultSet;

use Iterator;

abstract class AbstractResultSet implements Iterator, ResultSetInterface
{
    public function initialize(array|Iterator|IteratorAggregate|ResultInterface $dataSource) : self;
    public function getDataSource() : Iterator|IteratorAggregate|ResultInterface;
    public function getFieldCount() : int;

    /** Iterator */
    public function next() : mixed;
    public function key() : string|int;
    public function current() : mixed;
    public function valid() : bool;
    public function rewind() : void;

    /** countable */
    public function count() : int;

    /** get rows as array */
    public function toArray() : array;
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

class UserEntity
{
    protected $first_name;
    protected $last_name;

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;
    }

    public function setLastName($lastName)
    {
        $this->last_name = $lastName;
    }
}

$statement = $driver->createStatement($sql);
$statement->prepare($parameters);
$result = $statement->execute();

if ($result instanceof ResultInterface && $result->isQueryResult()) {
    $resultSet = new HydratingResultSet(new ReflectionHydrator, new UserEntity);
    $resultSet->initialize($result);

    foreach ($resultSet as $user) {
        echo $user->getFirstName() . ' ' . $user->getLastName() . PHP_EOL;
    }
}
```

For more information, see the [laminas-hydrator](https://docs.laminas.dev/laminas-hydrator/)
documentation to get a better sense of the different strategies that can be
employed in order to populate a target object.
