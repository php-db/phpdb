# Usage in a laminas-mvc Application

For installation instructions, see [Installation](../index.md#installation).

## Configuration

The adapter factory is already wired into the service manager. You only
need to provide the `db` configuration in `config/autoload/db.global.php`:

```php title="config/autoload/db.global.php"
<?php

declare(strict_types=1);

use PhpDb\Adapter\Driver\Pdo;

return [
    'db' => [
        'driver'     => Pdo::class,
        'connection' => [
            'hostname'       => (string) getenv('DB_HOSTNAME') ?: 'localhost',
            'username'       => (string) getenv('DB_USERNAME'),
            'password'       => (string) getenv('DB_PASSWORD'),
            'database'       => (string) getenv('DB_DATABASE'),
            'port'           => (string) getenv('DB_PORT') ?: '3306',
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

use PhpDb\Adapter\Driver\Pdo;

return [
    'db' => [
        'adapters' => [
            'ReadAdapter' => [
                'driver'     => Pdo::class,
                'connection' => [
                    'hostname'       => (string) getenv('DB_READ_HOSTNAME') ?: 'localhost',
                    'username'       => (string) getenv('DB_READ_USERNAME'),
                    'password'       => (string) getenv('DB_READ_PASSWORD'),
                    'database'       => (string) getenv('DB_READ_DATABASE'),
                    'port'           => (string) getenv('DB_READ_PORT') ?: '3306',
                    'charset'        => 'utf8',
                    'driver_options' => [],
                ],
                'options'    => [
                    'buffer_results' => true,
                ],
            ],
            'WriteAdapter' => [
                'driver'     => Pdo::class,
                'connection' => [
                    'hostname'       => (string) getenv('DB_WRITE_HOSTNAME') ?: 'localhost',
                    'username'       => (string) getenv('DB_WRITE_USERNAME'),
                    'password'       => (string) getenv('DB_WRITE_PASSWORD'),
                    'database'       => (string) getenv('DB_WRITE_DATABASE'),
                    'port'           => (string) getenv('DB_WRITE_PORT') ?: '3306',
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
use PhpDb\Mysql\Driver\Mysql;
use PhpDb\Mysql\Platform\Mysql as MysqlPlatform;

$driver = new Mysql([
    'hostname' => 'localhost',
    'database' => 'my_database',
    'username' => 'my_username',
    'password' => 'my_password',
]);

$adapter = new Adapter($driver, new MysqlPlatform());
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

    public function setDbAdapter(AdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    public function getDbAdapter(): ?AdapterInterface
    {
        return $this->adapter ?? null;
    }
}
```

## Running with Docker

For Docker deployment instructions including Dockerfiles,
Nginx/Apache configuration, MySQL/PostgreSQL setup, and complete
docker-compose examples, see the
[Docker Deployment Guide](../docker-deployment.md).
