# Usage in a Mezzio Application

The minimal installation for a Mezzio-based application doesn't include any database features.

## When installing the Mezzio Skeleton Application

While `Composer` is [installing the Mezzio Application](https://docs.mezzio.dev/mezzio/v3/getting-started/skeleton/), you can add the `phpdb` package after the skeleton is created.

## Adding to an existing Mezzio Skeleton Application

If the Mezzio application is already created, then use Composer to [add the phpdb](../index.md) package:

```bash
composer require phpdb/phpdb
```

## Service Configuration

Now that the phpdb package is installed, you need to configure the adapter through Mezzio's dependency injection container.

### Configuring the adapter

Mezzio uses PSR-11 containers and typically uses laminas-servicemanager or another DI container. The adapter configuration goes in your application's configuration files.

Create a configuration file `config/autoload/database.global.php` to define database settings:

### Working with a SQLite database

SQLite is a lightweight option to have the application working with a database.

Here is an example of the configuration array for a SQLite database.
Assuming the SQLite file path is `data/sample.sqlite`, the following configuration will produce the adapter:

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function ($container) {
                return new Adapter([
                    'driver' => 'Pdo_Sqlite',
                    'database' => 'data/sample.sqlite',
                ]);
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

The `data/` filepath for the SQLite file is relative to your application root directory.

### Working with a MySQL database

Unlike a SQLite database, the MySQL database adapter requires a MySQL server.

Here is an example of a configuration array for a MySQL database.

Create `config/autoload/database.local.php` for environment-specific credentials:

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function ($container) {
                return new Adapter([
                    'driver' => 'Pdo_Mysql',
                    'database' => 'your_database_name',
                    'username' => 'your_mysql_username',
                    'password' => 'your_mysql_password',
                    'hostname' => 'localhost',
                    'charset' => 'utf8mb4',
                    'driver_options' => [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                    ],
                ]);
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

### Working with PostgreSQL database

For PostgreSQL support:

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function ($container) {
                return new Adapter([
                    'driver' => 'Pdo_Pgsql',
                    'database' => 'your_database_name',
                    'username' => 'your_pgsql_username',
                    'password' => 'your_pgsql_password',
                    'hostname' => 'localhost',
                    'port' => 5432,
                    'charset' => 'utf8',
                ]);
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

## Working with the adapter

Once you have configured an adapter, as in the above examples, you now have a `PhpDb\Adapter\Adapter` available to your application through dependency injection.

### In Request Handlers

Mezzio uses request handlers (also known as middleware) that receive dependencies through constructor injection:

```php
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
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

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class UserListHandlerFactory
{
    public function __invoke(ContainerInterface $container): UserListHandler
    {
        return new UserListHandler(
            $container->get(AdapterInterface::class)
        );
    }
}
```

### Registering the Handler

Register your handler factory in `config/autoload/dependencies.global.php`:

```php
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

For more structured database interactions, use TableGateway with dependency injection:

```php
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

```php
<?php

declare(strict_types=1);

namespace App\Table;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class UsersTableFactory
{
    public function __invoke(ContainerInterface $container): UsersTable
    {
        return new UsersTable(
            $container->get(AdapterInterface::class)
        );
    }
}
```

Register the table factory:

```php
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

```php
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $users = $this->usersTable->findActiveUsers();

        return new JsonResponse(['users' => $users]);
    }
}
```

You can read more about the [adapter in the adapter chapter of the documentation](../adapter.md) and [TableGateway in the table gateway chapter](../table-gateway.md).

## Environment-based Configuration

For production deployments, use environment variables to configure database credentials:

### Using dotenv

Install `vlucas/phpdotenv`:

```bash
composer require vlucas/phpdotenv
```

Create a `.env` file in your project root:

```env
DB_DRIVER=Pdo_Mysql
DB_DATABASE=myapp_production
DB_USERNAME=dbuser
DB_PASSWORD=secure_password
DB_HOSTNAME=mysql-server
DB_PORT=3306
DB_CHARSET=utf8mb4
```

Load environment variables in `public/index.php`:

```php
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

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function ($container) {
                return new Adapter([
                    'driver' => $_ENV['DB_DRIVER'] ?? 'Pdo_Mysql',
                    'database' => $_ENV['DB_DATABASE'] ?? 'myapp',
                    'username' => $_ENV['DB_USERNAME'] ?? 'root',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'hostname' => $_ENV['DB_HOSTNAME'] ?? 'localhost',
                    'port' => $_ENV['DB_PORT'] ?? '3306',
                    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                ]);
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

## Running with Docker

When working with a MySQL database and when running the application with Docker, some files need to be added or adjusted.

### Adding the MySQL extension to the PHP container

#### Option 1: Nginx with PHP-FPM (Recommended)

For an nginx-based setup with PHP-FPM, create a `Dockerfile`:

```dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

Create an nginx configuration file at `docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Update your `docker-compose.yml` to include nginx:

```yaml
  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
```

#### Option 2: Apache

For an Apache-based setup, create a `Dockerfile`:

```dockerfile
FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y git zlib1g-dev libzip-dev \
 && docker-php-ext-install zip pdo_mysql \
 && a2enmod rewrite \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

### Adding the MySQL container

Change the `docker-compose.yml` file to add a new container for MySQL:

```yaml
version: "3.8"

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
    depends_on:
      - mysql
    environment:
      - DB_DRIVER=Pdo_Mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_HOSTNAME=mysql
      - DB_PORT=3306

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    command: --default-authentication-plugin=mysql_native_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}

volumes:
  mysql_data:
```

Though it is not the topic to explain how to write a `docker-compose.yml` file, a few details need to be highlighted:

- The name of the container is `mysql`.
- MySQL database files will be persisted in a named volume `mysql_data`.
- SQL schemas will need to be added to the `./docker/mysql/init/` directory so that Docker will be able to build and populate the database(s).
- The MySQL docker image uses environment variables to set the database name, user, and passwords.
- The `depends_on` directive ensures MySQL starts before the application container.

### Adding PostgreSQL Container

For PostgreSQL instead of MySQL:

```yaml
version: "3.8"

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
    depends_on:
      - postgres
    environment:
      - DB_DRIVER=Pdo_Pgsql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_HOSTNAME=postgres
      - DB_PORT=5432

  postgres:
    image: postgres:15-alpine
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/postgres/init:/docker-entrypoint-initdb.d
    environment:
      - POSTGRES_DB=${DB_DATABASE}
      - POSTGRES_USER=${DB_USERNAME}
      - POSTGRES_PASSWORD=${DB_PASSWORD}

volumes:
  postgres_data:
```

Update the `Dockerfile` to install the PostgreSQL extension:

```dockerfile
RUN docker-php-ext-install pdo_pgsql
```

### Adding phpMyAdmin

Optionally, you can also add a container for phpMyAdmin:

```yaml
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8081:80"
    depends_on:
      - mysql
    environment:
      - PMA_HOST=mysql
      - PMA_PORT=3306
```

### Complete Docker Compose Example

#### With Nginx (Recommended)

Putting everything together with nginx:

```yaml
version: "3.8"

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www
    depends_on:
      - mysql
    environment:
      - DB_DRIVER=Pdo_Mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_HOSTNAME=mysql
      - DB_PORT=3306

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    command: --default-authentication-plugin=mysql_native_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8081:80"
    depends_on:
      - mysql
    environment:
      - PMA_HOST=mysql
      - PMA_PORT=3306

volumes:
  mysql_data:
```

#### With Apache

For Apache-based deployment:

```yaml
version: "3.8"

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
    depends_on:
      - mysql
    environment:
      - DB_DRIVER=Pdo_Mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_HOSTNAME=mysql
      - DB_PORT=3306

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    command: --default-authentication-plugin=mysql_native_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8081:80"
    depends_on:
      - mysql
    environment:
      - PMA_HOST=mysql
      - PMA_PORT=3306

volumes:
  mysql_data:
```

### Defining credentials

The `docker-compose.yml` file uses environment variables to define the credentials.

Docker will read the environment variables from a `.env` file:

```env
DB_DATABASE=mezzio_app
DB_USERNAME=appuser
DB_PASSWORD=apppassword
MYSQL_ROOT_PASSWORD=rootpassword
```

### Initiating the database schemas

At build, if the volume is empty, Docker will create the MySQL database with any `.sql` files found in the `./docker/mysql/init/` directory.

Create `docker/mysql/init/01-schema.sql`:

```sql
USE mezzio_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_status ON users(status);
```

Create `docker/mysql/init/02-seed.sql`:

```sql
USE mezzio_app;

INSERT INTO users (username, email, status) VALUES
    ('alice', 'alice@example.com', 'active'),
    ('bob', 'bob@example.com', 'active'),
    ('charlie', 'charlie@example.com', 'inactive');
```

If multiple `.sql` files are present, they are executed in alphanumeric order, which is why the files are prefixed with numbers.

## Testing with Database

For integration testing with a real database in Mezzio:

### Create a Test Configuration

Create `config/autoload/database.test.php`:

```php
<?php

declare(strict_types=1);

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'factories' => [
            Adapter::class => function ($container) {
                return new Adapter([
                    'driver' => 'Pdo_Sqlite',
                    'database' => ':memory:',
                ]);
            },
        ],
        'aliases' => [
            AdapterInterface::class => Adapter::class,
        ],
    ],
];
```

### Use in PHPUnit Tests

```php
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
            "INSERT INTO users (username, email, status) VALUES
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

Always inject the adapter or table gateway through constructors, never instantiate directly in handlers.

### Separate Database Logic

Create repository or table gateway classes to separate database logic from HTTP handlers:

```php
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

        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

        return iterator_to_array($results);
    }

    public function findById(int $id): ?array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('users');
        $select->where(['id' => $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $row = $results->current();

        return $row ? (array) $row : null;
    }
}
```

### Use Configuration Factories

Centralize adapter configuration in factory classes for better maintainability and testability.

### Handle Exceptions

Always wrap database operations in try-catch blocks:

```php
use PhpDb\Adapter\Exception\RuntimeException;

public function handle(ServerRequestInterface $request): ResponseInterface
{
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