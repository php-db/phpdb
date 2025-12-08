# Usage in a laminas-mvc Application

For installation instructions, see [Installation](../index.md#installation).

## Service Configuration

Now that the phpdb packages are installed, you need to configure the adapter through your application's service manager.

### Configuring the Adapter

Create a configuration file `config/autoload/database.global.php` (or `local.php` for credentials) to define database settings.

### Working with a SQLite database

SQLite is a lightweight option to have the application working with a database.

Here is an example of the configuration array for a SQLite database.
Assuming the SQLite file path is `data/sample.sqlite`, the following configuration will produce the adapter:

### SQLite adapter configuration

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sqlite\Driver\Sqlite;
use PhpDb\Sqlite\Platform\Sqlite as SqlitePlatform;
use Psr\Container\ContainerInterface;

return [
    'service_manager' => [
        'factories' => [
            Adapter::class => function (ContainerInterface $container) {
                $driver = new Sqlite([
                    'database' => 'data/sample.sqlite',
                ]);
                return new Adapter($driver, new SqlitePlatform());
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

The `data/` filepath for the SQLite file is the default `data/` directory from the Laminas MVC application.

### Working with a MySQL database

Unlike a SQLite database, the MySQL database adapter requires a MySQL server.

Here is an example of a configuration array for a MySQL database:

### MySQL adapter configuration

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver\Mysql;
use PhpDb\Mysql\Platform\Mysql as MysqlPlatform;
use Psr\Container\ContainerInterface;

return [
    'service_manager' => [
        'factories' => [
            Adapter::class => function (ContainerInterface $container) {
                $driver = new Mysql([
                    'database' => 'your_database_name',
                    'username' => 'your_mysql_username',
                    'password' => 'your_mysql_password',
                    'hostname' => 'localhost',
                    'charset' => 'utf8mb4',
                ]);
                return new Adapter($driver, new MysqlPlatform());
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

### Working with PostgreSQL database

PostgreSQL support is coming soon. Once the `php-db/postgres` package is available:

### PostgreSQL adapter configuration

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Postgres\Driver\Postgres;
use PhpDb\Postgres\Platform\Postgres as PostgresPlatform;
use Psr\Container\ContainerInterface;

return [
    'service_manager' => [
        'factories' => [
            Adapter::class => function (ContainerInterface $container) {
                $driver = new Postgres([
                    'database' => 'your_database_name',
                    'username' => 'your_pgsql_username',
                    'password' => 'your_pgsql_password',
                    'hostname' => 'localhost',
                    'port' => 5432,
                ]);
                return new Adapter($driver, new PostgresPlatform());
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

## Working with the adapter

Once you have configured an adapter, as in the above examples, you now have a `PhpDb\Adapter\Adapter` available to your application.

A factory for a class that consumes an adapter can pull the adapter from the container:

### Retrieving the adapter from the service container

```php
use PhpDb\Adapter\AdapterInterface;

$adapter = $container->get(AdapterInterface::class);
```

You can read more about the [adapter in the adapter chapter of the documentation](../adapter.md).

## Adapter-Aware Services with AdapterServiceDelegator

If you have services that implement `PhpDb\Adapter\AdapterAwareInterface`, you can use the `AdapterServiceDelegator` to automatically inject the database adapter.

### Using the Delegator

Register the delegator in your service configuration:

### Delegator configuration for adapter-aware services

```php
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

### Delegator configuration for multiple adapters

```php
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

### Implementing AdapterAwareInterface in a service class

```php
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

For Docker deployment instructions including Dockerfiles, Nginx/Apache configuration, MySQL/PostgreSQL setup, and complete docker-compose examples, see the [Docker Deployment Guide](../docker-deployment.md).
