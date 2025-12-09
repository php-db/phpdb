# Usage in a Mezzio Application

For installation instructions, see [Installation](../index.md#installation).

## Configuration

The adapter factory is already wired into the container. You only
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

Once configured, retrieve the adapter from the container:

```php title="Retrieving the adapter from the container"
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

## Running with Docker

For Docker deployment instructions including Dockerfiles,
Nginx/Apache configuration, MySQL/PostgreSQL setup, and complete
docker-compose examples, see the
[Docker Deployment Guide](../docker-deployment.md).