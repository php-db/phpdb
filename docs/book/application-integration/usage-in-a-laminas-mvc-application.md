# Usage in a laminas-mvc Application

For installation instructions, see [Installation](../index.md#installation).

## Configuration

The adapter factory is already wired into the service manager. You only
need to provide the `AdapterInterface::class` configuration in `config/autoload/db.global.php`:

```php title="config/autoload/db.global.php"
<?php

declare(strict_types=1);

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver;

return [
    AdapterInterface::class => [
        'driver'     => Driver::class,
        'connection' => [
            'hostname'       => 'localhost', // or use getenv('DB_HOSTNAME') for environment variable configuration
            'username'       => 'your_username',
            'password'       => 'your_password',
            'database'       => 'your_database',
            'port'           => '3306',
            'charset'        => 'utf8',
            'driver_options' => [],
        ],
        'options'    => [
            'buffer_results' => false,
        ],
    ],
];
```

### Named Adapters

For applications requiring multiple database connections (e.g., read/write
separation), use named adapters:

```php title="config/autoload/db.global.php"
<?php

declare(strict_types=1);

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver;

return [
    AdapterInterface::class => [
        'adapters' => [
            'ReadAdapter'  => [
                'driver'     => Driver::class,
                'connection' => [
                    'hostname'       => 'localhost', // or use getenv('DB_READ_HOSTNAME') for environment variable configuration
                    'username'       => 'your_username',
                    'password'       => 'your_password',
                    'database'       => 'your_database',
                    'port'           => '3306',
                    'charset'        => 'utf8',
                    'driver_options' => [],
                ],
                'options'    => [
                    'buffer_results' => true,
                ],
            ],
            'WriteAdapter' => [
                'driver'     => Driver::class,
                'connection' => [
                    'hostname'       => 'localhost', // or use getenv('DB_WRITE_HOSTNAME') for environment variable configuration
                    'username'       => 'your_username',
                    'password'       => 'your_password',
                    'database'       => 'your_database',
                    'port'           => '3306',
                    'charset'        => 'utf8',
                    'driver_options' => [],
                ],
                'options'    => [
                    'buffer_results' => false,
                ],
            ],
        ],
    ],
];
```

## Working with the Adapter

### Container-Managed Instantiation

Once configured, retrieve the adapter from the service manager:

```php title="Retrieving the adapter from the service container"
use PhpDb\Adapter\AdapterInterface;

$adapter = $container->get(AdapterInterface::class);
```

### Manual Instantiation

If you need to create an adapter without the container:

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\AdapterPlatform;

$adapter = new Adapter(
    new Driver(
        new Connection([
            'hostname' => 'localhost',
            'database' => 'my_database',
            'username' => 'my_username',
            'password' => 'my_password',
        ]),
    new AdapterPlatform()
    )
);
```

You can read more about the
[adapter in the adapter chapter of the documentation](../adapter.md).

## Adapter-Aware Services with AdapterServiceDelegator

If you have services that implement `PhpDb\Adapter\AdapterAwareInterface`,
you can use the `AdapterServiceDelegator` to automatically inject the
database adapter.

### Using the Delegator

Register the delegator in your service configuration:

```php title="Delegator configuration for adapter-aware services"
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Container\AdapterServiceDelegator;

return [
    'service_manager' => [
        'delegators' => [
            MyDatabaseService::class => [
                new AdapterServiceDelegator(AdapterInterface::class),
            ],
        ],
    ],
];
```

### Multiple Adapters

When using multiple adapters, you can specify which adapter to inject:

```php title="Delegator configuration for multiple adapters"
use PhpDb\Container\AdapterServiceDelegator;

return [
    'service_manager' => [
        'delegators' => [
            ReadService::class => [
                new AdapterServiceDelegator('db.reader'),
            ],
            WriteService::class => [
                new AdapterServiceDelegator('db.writer'),
            ],
        ],
    ],
];
```

### Implementing AdapterAwareInterface

Your service class must implement `AdapterAwareInterface`:

```php title="Implementing AdapterAwareInterface in a service class"
use PhpDb\Adapter\AdapterAwareInterface;
use PhpDb\Adapter\AdapterInterface;

class MyDatabaseService implements AdapterAwareInterface
{
    private AdapterInterface $adapter;

    public function setDbAdapter(AdapterInterface $adapter): static
    {
        $this->adapter = $adapter;
        return $this;
    }
}
```

## Running with Docker

For Docker deployment instructions including Dockerfiles,
Nginx/Apache configuration, MySQL/PostgreSQL setup, and complete
docker-compose examples, see the
[Docker Deployment Guide](../docker-deployment.md).
