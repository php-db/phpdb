# Introduction

phpdb is a database abstraction layer providing:

- **Database adapters** for connecting to various database vendors
  (MySQL, PostgreSQL, SQLite, and more)
- **SQL abstraction** for building database-agnostic queries
  programmatically
- **DDL abstraction** for creating and modifying database schemas
- **Result set abstraction** for working with query results
- **TableGateway and RowGateway** implementations for the Table Data
  Gateway and Row Data Gateway patterns

## Installation

Install the core package via Composer:

```bash
composer require php-db/phpdb
```

Additionally, install the driver package(s) for the database(s) you plan to
use:

```bash
# For MySQL/MariaDB support
composer require php-db/mysql

# For SQLite support
composer require php-db/sqlite

# For PostgreSQL support (coming soon)
composer require php-db/postgres
```

### Mezzio

phpdb provides a `ConfigProvider` that is automatically registered when using
[laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/).

If you are not using the component installer, add the following to your
`config/config.php`:

```php
$aggregator = new ConfigAggregator([
    \PhpDb\ConfigProvider::class,
    // ... other providers
]);
```

For detailed Mezzio configuration including adapter setup and dependency
injection, see the
[Mezzio integration guide](application-integration/usage-in-a-mezzio-application.md).

### Laminas MVC

phpdb provides module configuration that is automatically registered when using
[laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/).

If you are not using the component installer, add the module to your
`config/modules.config.php`:

```php
return [
    'PhpDb',
    // ... other modules
];
```

For detailed Laminas MVC configuration including adapter setup and service
manager integration, see the
[Laminas MVC integration guide](application-integration/usage-in-a-laminas-mvc-application.md).

### Optional Dependencies

The following packages provide additional functionality:

- **laminas/laminas-hydrator** - Required for using `HydratingResultSet` to
  hydrate result rows into objects
- **laminas/laminas-eventmanager** - Enables event-driven profiling and
  logging of database operations

Install optional dependencies as needed:

```bash
composer require laminas/laminas-hydrator
composer require laminas/laminas-eventmanager
```

## Quick Start

Once installed and configured, you can start using phpdb immediately:

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Sql\Sql;

// Assuming $adapter is configured via your framework's DI container
$sql = new Sql($adapter);

// Build a SELECT query
$select = $sql->select('users');
$select->where(['status' => 'active']);
$select->order('created_at DESC');
$select->limit(10);

// Execute and iterate results
$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();

foreach ($results as $row) {
    echo $row['username'] . "\n";
}
```

Or use the TableGateway for a higher-level abstraction:

```php
use PhpDb\TableGateway\TableGateway;

$usersTable = new TableGateway('users', $adapter);

// Select rows
$activeUsers = $usersTable->select(['status' => 'active']);

// Insert a new row
$usersTable->insert([
    'username' => 'newuser',
    'email' => 'newuser@example.com',
    'status' => 'active',
]);

// Update rows
$usersTable->update(
    ['status' => 'inactive'],
    ['last_login < ?' => '2024-01-01']
);

// Delete rows
$usersTable->delete(['id' => 123]);
```

## Documentation Overview

- **[Adapters](adapter.md)** - Database connection and configuration
- **[SQL Abstraction](sql/intro.md)** - Building SELECT, INSERT, UPDATE,
  and DELETE queries
- **[DDL Abstraction](sql-ddl/intro.md)** - Creating and modifying database
  schemas
- **[Result Sets](result-set/intro.md)** - Working with query results
- **[Table Gateways](table-gateway.md)** - Table Data Gateway pattern
  implementation
- **[Row Gateways](row-gateway.md)** - Row Data Gateway pattern
  implementation
- **[Metadata](metadata/intro.md)** - Database introspection and schema
  information
