# AdapterAwareTrait

The trait `PhpDb\Adapter\AdapterAwareTrait`, which provides implementation
for `PhpDb\Adapter\AdapterAwareInterface`, and allowed removal of
duplicated implementations in several components of Laminas or in custom
applications.

The interface defines only the method `setDbAdapter()` with one parameter for an
instance of `PhpDb\Adapter\Adapter`:

```php
public function setDbAdapter(\PhpDb\Adapter\Adapter $adapter) : self;
```

## Basic Usage

### Create Class and Add Trait

```php
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\AdapterAwareInterface;

class Example implements AdapterAwareInterface
{
    use AdapterAwareTrait;
}
```

### Create and Set Adapter

[Create a database adapter](../adapter.md#creating-an-adapter-using-configuration) and set the adapter to the instance of the `Example`
class:

```php
$adapter = new PhpDb\Adapter\Adapter([
    'driver'   => 'Pdo_Sqlite',
    'database' => 'path/to/sqlite.db',
]);

$example = new Example();
$example->setAdapter($adapter);
```

## AdapterServiceDelegator

The [delegator](https://docs.laminas.dev/laminas-servicemanager/delegators/)
`PhpDb\Adapter\AdapterServiceDelegator` can be used to set a database
adapter via the [service manager of laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/quick-start/).

The delegator tries to fetch a database adapter via the name
`PhpDb\Adapter\AdapterInterface` from the service container and sets the
adapter to the requested service. The adapter itself must be an instance of
`PhpDb\Adapter\Adapter`.

> ### Integration for Mezzio and laminas-mvc based Applications
>
> In a Mezzio or laminas-mvc based application the database adapter is already
> registered during the installation with the laminas-component-installer.

### Create Class and Use Trait

Create a class and add the trait `AdapterAwareTrait`.

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;

class Example implements AdapterAwareInterface
{
    use AdapterAwareTrait;

    public function getAdapter() : ?Adapter
    {
        return $this->adapter;
    }
}
```

(A getter method is also added for demonstration.)

### Create and Configure Service Manager

Create and [configured the service manager](https://docs.laminas.dev/laminas-servicemanager/configuring-the-service-manager/):

```php
use Interop\Container\ContainerInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\AdapterServiceDelegator;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\AdapterAwareInterface;

$serviceManager = new Laminas\ServiceManager\ServiceManager([
    'factories' => [
        // Database adapter
        AdapterInterface::class => static function(ContainerInterface $container) {
            return new PhpDb\Adapter\Adapter([
                'driver'   => 'Pdo_Sqlite',
                'database' => 'path/to/sqlite.db',
            ]);
        }
    ],
    'invokables' => [
        // Example class
        Example::class => Example::class,
    ],
    'delegators' => [
        // Delegator for Example class to set the adapter
        Example::class => [
            AdapterServiceDelegator::class,
        ],
    ],
]);
```

### Get Instance of Class

[Retrieving an instance](https://docs.laminas.dev/laminas-servicemanager/quick-start/#3-retrieving-objects)
of the `Example` class with a database adapter:

```php
/** @var Example $example */
$example = $serviceManager->get(Example::class);

var_dump($example->getAdapter() instanceof PhpDb\Adapter\Adapter); // true
```

## Concrete Implementations

The validators [`Db\RecordExists` and `Db\NoRecordExists`](https://docs.laminas.dev/laminas-validator/validators/db/)
implements the trait and the plugin manager of [laminas-validator](https://docs.laminas.dev/laminas-validator/)
includes the delegator to set the database adapter for both validators.
