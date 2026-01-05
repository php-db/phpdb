# AdapterAwareTrait

`PhpDb\Adapter\AdapterAwareTrait` provides a standard implementation of
`AdapterAwareInterface` for injecting database adapters into your classes.

```php
public function setDbAdapter(\PhpDb\Adapter\Adapter $adapter) : self;
```

## Basic Usage

```php
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\AdapterAwareInterface;

class Example implements AdapterAwareInterface
{
    use AdapterAwareTrait;
}

// Set adapter (see adapter.md for creation)
$example = new Example();
$example->setDbAdapter($adapter);
```

## AdapterServiceDelegator

The [delegator](https://docs.laminas.dev/laminas-servicemanager/delegators/)
`PhpDb\Adapter\AdapterServiceDelegator` can be used to set a database adapter
via the [service manager of laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/quick-start/).

The delegator tries to fetch a database adapter via the name
`PhpDb\Adapter\AdapterInterface` from the service container and sets the
adapter to the requested service. The adapter itself must be an instance of
`PhpDb\Adapter\Adapter`.

> ### Integration for Mezzio and laminas-mvc based Applications
>
> In a Mezzio or laminas-mvc based application the database adapter is
> already registered during the installation with the
> laminas-component-installer.

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

Create and [configure the service manager](
https://docs.laminas.dev/laminas-servicemanager/configuring-the-service-manager/):

```php
use Psr\Container\ContainerInterface;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\AdapterServiceDelegator;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\AdapterAwareInterface;
use PhpDb\Sqlite\Driver\Sqlite;
use PhpDb\Sqlite\Platform\Sqlite as SqlitePlatform;

$serviceManager = new Laminas\ServiceManager\ServiceManager([
    'factories' => [
        // Database adapter
        AdapterInterface::class => static function(
            ContainerInterface $container
        ) {
            $driver = new Sqlite([
                'database' => 'path/to/sqlite.db',
            ]);
            return new Adapter($driver, new SqlitePlatform());
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

[Retrieving an instance](
https://docs.laminas.dev/laminas-servicemanager/quick-start/#3-retrieving-objects)
of the `Example` class with a database adapter:

```php
/** @var Example $example */
$example = $serviceManager->get(Example::class);

var_dump(
    $example->getAdapter() instanceof PhpDb\Adapter\AdapterInterface
); // true
```

The [laminas-validator](
https://docs.laminas.dev/laminas-validator/validators/db/)
`Db\RecordExists` and `Db\NoRecordExists` validators use this pattern.
