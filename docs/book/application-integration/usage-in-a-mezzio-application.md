# Usage in a Mezzio Application

For installation instructions, see [Installation](../index.md#installation).

## Service Configuration

Now that the phpdb packages are installed, you need to configure the
adapter through Mezzio's dependency injection container.

Mezzio uses PSR-11 containers and typically uses laminas-servicemanager
or another DI container. The adapter configuration goes in your
application's configuration files.

Create a configuration file `config/autoload/database.global.php` to
define database settings.

### Working with a SQLite database

SQLite is a lightweight option to have the application working with a database.

Here is an example of the configuration array for a SQLite database.
Assuming the SQLite file path is `data/sample.sqlite`, the following
configuration will produce the adapter:

```php title="SQLite adapter configuration"
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sqlite\Driver\Sqlite;
use PhpDb\Sqlite\Platform\Sqlite as SqlitePlatform;
use Psr\Container\ContainerInterface;

return [
    'dependencies' => [
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

The `data/` filepath for the SQLite file is relative to your application
root directory.

### Working with a MySQL database

Unlike a SQLite database, the MySQL database adapter requires a MySQL server.

Here is an example of a configuration array for a MySQL database.

Create `config/autoload/database.local.php` for environment-specific
credentials:

```php title="MySQL adapter configuration"
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver\Mysql;
use PhpDb\Mysql\Platform\Mysql as MysqlPlatform;
use Psr\Container\ContainerInterface;

return [
    'dependencies' => [
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

PostgreSQL support is coming soon. Once the `php-db/postgres` package is
available:

```php title="PostgreSQL adapter configuration"
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Postgres\Driver\Postgres;
use PhpDb\Postgres\Platform\Postgres as PostgresPlatform;
use Psr\Container\ContainerInterface;

return [
    'dependencies' => [
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

Once you have configured an adapter, as in the above examples,
you now have a `PhpDb\Adapter\Adapter` available to your application
through dependency injection.

### In Request Handlers

Mezzio uses request handlers (also known as middleware) that receive
dependencies through constructor injection:

```php title="Request handler with database adapter injection"
<?php

declare(strict_types=1);

namespace App\Handler;

use PhpDb\Adapter\AdapterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class UserListHandler implements RequestHandlerInterface
{
    public function __construct(
        private AdapterInterface $adapter
    ) {
    }

    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        $results = $this->adapter->query(
            'SELECT id, username, email FROM users WHERE status = ?',
            ['active']
        );

        $users = [];
        foreach ($results as $row) {
            $users[] = [
                'id' => $row->id,
                'username' => $row->username,
                'email' => $row->email,
            ];
        }

        return new JsonResponse(['users' => $users]);
    }
}
```

### Creating a Handler Factory

You need to create a factory for your handler that injects the adapter:

```php title="Handler factory implementation"
<?php

declare(strict_types=1);

namespace App\Handler;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class UserListHandlerFactory
{
    public function __invoke(
        ContainerInterface $container
    ): UserListHandler {
        return new UserListHandler(
            $container->get(AdapterInterface::class)
        );
    }
}
```

### Registering the Handler

Register your handler factory in
`config/autoload/dependencies.global.php`:

```php title="Registering the handler in dependencies configuration"
<?php

declare(strict_types=1);

use App\Handler\UserListHandler;
use App\Handler\UserListHandlerFactory;

return [
    'dependencies' => [
        'invokables' => [
            // ... other invokables
        ],
        'factories' => [
            UserListHandler::class => UserListHandlerFactory::class,
            // ... other factories
        ],
    ],
];
```

### Using with TableGateway

For more structured database interactions, use TableGateway with
dependency injection:

```php title="Extending TableGateway for custom database operations"
<?php

declare(strict_types=1);

namespace App\Table;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\TableGateway;

class UsersTable extends TableGateway
{
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct('users', $adapter);
    }

    public function findActiveUsers(): array
    {
        $resultSet = $this->select(['status' => 'active']);
        return iterator_to_array($resultSet);
    }

    public function findUserById(int $id): ?array
    {
        $rowset = $this->select(['id' => $id]);
        $row = $rowset->current();

        return $row ? (array) $row : null;
    }
}
```

Create a factory for the table:

```php title="Factory for the TableGateway class"
<?php

declare(strict_types=1);

namespace App\Table;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class UsersTableFactory
{
    public function __invoke(
        ContainerInterface $container
    ): UsersTable {
        return new UsersTable(
            $container->get(AdapterInterface::class)
        );
    }
}
```

Register the table factory:

```php title="Registering the table factory in dependencies"
<?php

declare(strict_types=1);

use App\Table\UsersTable;
use App\Table\UsersTableFactory;

return [
    'dependencies' => [
        'factories' => [
            UsersTable::class => UsersTableFactory::class,
        ],
    ],
];
```

Use in your handler:

```php title="Using TableGateway in a request handler"
<?php

declare(strict_types=1);

namespace App\Handler;

use App\Table\UsersTable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class UserListHandler implements RequestHandlerInterface
{
    public function __construct(
        private UsersTable $usersTable
    ) {
    }

    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        $users = $this->usersTable->findActiveUsers();

        return new JsonResponse(['users' => $users]);
    }
}
```

You can read more about the
[adapter in the adapter chapter of the documentation](../adapter.md) and
[TableGateway in the table gateway chapter](../table-gateway.md).

## Environment-based Configuration

For production deployments, use environment variables to configure
database credentials:

### Using dotenv

Install `vlucas/phpdotenv`:

```bash title="Installing the phpdotenv package"
composer require vlucas/phpdotenv
```

Create a `.env` file in your project root:

### Environment variables configuration file

```env
DB_TYPE=mysql
DB_DATABASE=myapp_production
DB_USERNAME=dbuser
DB_PASSWORD=secure_password
DB_HOSTNAME=mysql-server
DB_PORT=3306
DB_CHARSET=utf8mb4
```

Load environment variables in `public/index.php`:

```php title="Loading environment variables in the application bootstrap"
<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$container = require 'config/container.php';
```

Update your database configuration to use environment variables:

```php title="Dynamic adapter configuration using environment variables"
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver\Mysql;
use PhpDb\Mysql\Platform\Mysql as MysqlPlatform;
use PhpDb\Sqlite\Driver\Sqlite;
use PhpDb\Sqlite\Platform\Sqlite as SqlitePlatform;
use Psr\Container\ContainerInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function (
                ContainerInterface $container
            ) {
                $dbType = $_ENV['DB_TYPE'] ?? 'sqlite';

                if ($dbType === 'mysql') {
                    $driver = new Mysql([
                        'database' => $_ENV['DB_DATABASE'] ?? 'myapp',
                        'username' => $_ENV['DB_USERNAME'] ?? 'root',
                        'password' => $_ENV['DB_PASSWORD'] ?? '',
                        'hostname' => $_ENV['DB_HOSTNAME'] ?? 'localhost',
                        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                    ]);
                    return new Adapter($driver, new MysqlPlatform());
                }

                // Default to SQLite
                $driver = new Sqlite([
                    'database' =>
                        $_ENV['DB_DATABASE'] ?? 'data/app.sqlite',
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

## Running with Docker

For Docker deployment instructions including Dockerfiles,
Nginx/Apache configuration, MySQL/PostgreSQL setup, and complete
docker-compose examples, see the
[Docker Deployment Guide](../docker-deployment.md).

## Testing with Database

For integration testing with a real database in Mezzio:

### Create a Test Configuration

Create `config/autoload/database.test.php`:

```php title="Test database configuration with in-memory SQLite"
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sqlite\Driver\Sqlite;
use PhpDb\Sqlite\Platform\Sqlite as SqlitePlatform;
use Psr\Container\ContainerInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function (
                ContainerInterface $container
            ) {
                $driver = new Sqlite([
                    'database' => ':memory:',
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

### Use in PHPUnit Tests

```php title="PHPUnit test with database integration"
<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\UserListHandler;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ServerRequest;

class UserListHandlerTest extends TestCase
{
    private AdapterInterface $adapter;

    protected function setUp(): void
    {
        $container = require 'config/container.php';
        $this->adapter = $container->get(AdapterInterface::class);

        $this->adapter->query(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                username TEXT,
                email TEXT,
                status TEXT
            )'
        );

        $this->adapter->query(
            "INSERT INTO users (username, email, status)
             VALUES
                ('alice', 'alice@example.com', 'active'),
                ('bob', 'bob@example.com', 'active')"
        );
    }

    public function testHandleReturnsUserList(): void
    {
        $handler = new UserListHandler($this->adapter);
        $request = new ServerRequest();

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertCount(2, $body['users']);
    }
}
```

## Best Practices for Mezzio

### Use Dependency Injection

Always inject the adapter or table gateway through constructors,
never instantiate directly in handlers.

### Separate Database Logic

Create repository or table gateway classes to separate database logic
from HTTP handlers:

```php title="Repository pattern implementation for database operations"
<?php

declare(strict_types=1);

namespace App\Repository;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;

class UserRepository
{
    public function __construct(
        private AdapterInterface $adapter
    ) {
    }

    public function findAll(): array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('users');

        $statement =
            $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

        return iterator_to_array($results);
    }

    public function findById(int $id): ?array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('users');
        $select->where(['id' => $id]);

        $statement =
            $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $row = $results->current();

        return $row ? (array) $row : null;
    }
}
```

### Use Configuration Factories

Centralize adapter configuration in factory classes for better
maintainability and testability.

### Handle Exceptions

Always wrap database operations in try-catch blocks:

```php title="Exception handling for database operations"
use PhpDb\Adapter\Exception\RuntimeException;

public function handle(
    ServerRequestInterface $request
): ResponseInterface {
    try {
        $users = $this->usersTable->findActiveUsers();
        return new JsonResponse(['users' => $users]);
    } catch (RuntimeException $e) {
        return new JsonResponse(
            ['error' => 'Database error occurred'],
            500
        );
    }
}
```
