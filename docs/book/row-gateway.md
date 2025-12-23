# Row Gateways

`PhpDb\RowGateway` implements the
[Row Data Gateway pattern](http://www.martinfowler.com/eaaCatalog/rowDataGateway.html),
an object that wraps a single database row, providing `save()` and `delete()`
methods to persist changes.

`RowGatewayInterface` defines these methods:

## RowGatewayInterface Definition

```php
namespace PhpDb\RowGateway;

interface RowGatewayInterface
{
    public function save(): int;
    public function delete(): int;
}
```

## Quick start

`RowGateway` is generally used in conjunction with objects that produce
`PhpDb\ResultSet`s, though it may also be used standalone.  To use it
standalone, you need an `Adapter` instance and a set of data to work with.

The following demonstrates a basic use case.

```php title="Standalone RowGateway Usage"
use PhpDb\RowGateway\RowGateway;

// Query the database:
$resultSet = $adapter->query(
    'SELECT * FROM `user` WHERE `id` = ?',
    [2]
);

// Get array of data:
$rowData = $resultSet->current()->getArrayCopy();

// Create a row gateway:
$rowGateway = new RowGateway('id', 'my_table', $adapter);
$rowGateway->populate($rowData, true);

// Manipulate the row and persist it:
$rowGateway->first_name = 'New Name';
$rowGateway->save();

// Or delete this row:
$rowGateway->delete();
```

The workflow described above is greatly simplified when `RowGateway` is used
in conjunction with the
[TableGateway RowGatewayFeature](table-gateway.md#tablegateway-features). In
that paradigm, `select()` operations will produce a `ResultSet` that iterates
`RowGateway` instances.

As an example:

```php title="Using RowGateway with TableGateway"
use PhpDb\TableGateway\Feature\RowGatewayFeature;
use PhpDb\TableGateway\TableGateway;

$table = new TableGateway('artist', $adapter, new RowGatewayFeature('id'));
$results = $table->select(['id' => 2]);

$artistRow = $results->current();
$artistRow->name = 'New Name';
$artistRow->save();
```

## ActiveRecord Style Objects

If you wish to have custom behaviour in your `RowGateway` objects &mdash;
essentially making them behave similarly to the
[ActiveRecord](http://www.martinfowler.com/eaaCatalog/activeRecord.html)
pattern), pass a prototype object implementing the `RowGatewayInterface` to
the `RowGatewayFeature` constructor instead of a primary key:

```php title="Custom ActiveRecord-Style Implementation"
use PhpDb\TableGateway\Feature\RowGatewayFeature;
use PhpDb\TableGateway\TableGateway;
use PhpDb\RowGateway\RowGatewayInterface;

class Artist implements RowGatewayInterface
{
    protected $adapter;

    public function __construct($adapter)
    {
       $this->adapter = $adapter;
    }

    // ... save() and delete() implementations
}

$table = new TableGateway(
    'artist',
    $adapter,
    new RowGatewayFeature(new Artist($adapter))
);
```
