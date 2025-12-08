# Adapters

`PhpDb\Adapter\Adapter` is the central component that provides a unified interface to different PHP PDO extensions and database vendors. It abstracts both the database driver (connection management) and platform-specific SQL dialects.

## Package Architecture

Starting with version 0.4.x, PhpDb uses a modular package architecture. The core
`php-db/phpdb` package provides:

- Base adapter and interfaces
- Abstract PDO driver classes
- Platform abstractions
- SQL abstraction layer
- Result set handling
- Table and Row gateway implementations

Database-specific drivers are provided as separate packages:

| Package | Database | Status |
|---------|----------|--------|
| `php-db/mysql` | MySQL/MariaDB | Available |
| `php-db/sqlite` | SQLite | Available |
| `php-db/postgres` | PostgreSQL | Coming Soon |

## Quick Start

### MySQL Connection

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Mysql\Driver\Mysql;
use PhpDb\Mysql\Platform\Mysql as MysqlPlatform;

$driver = new Mysql([
    'database' => 'my_database',
    'username' => 'my_user',
    'password' => 'my_password',
    'hostname' => 'localhost',
]);

$adapter = new Adapter($driver, new MysqlPlatform());
```

### SQLite Connection

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Sqlite\Driver\Sqlite;
use PhpDb\Sqlite\Platform\Sqlite as SqlitePlatform;

$driver = new Sqlite([
    'database' => '/path/to/database.sqlite',
]);

$adapter = new Adapter($driver, new SqlitePlatform());
```

## The Adapter Class

The `Adapter` class provides the primary interface for database operations:

### Adapter Class Interface

```php
namespace PhpDb\Adapter;

use PhpDb\ResultSet;

class Adapter implements AdapterInterface, Profiler\ProfilerAwareInterface, SchemaAwareInterface
{
    public function __construct(
        Driver\DriverInterface $driver,
        Platform\PlatformInterface $platform,
        ResultSet\ResultSetInterface $queryResultSetPrototype = new ResultSet\ResultSet(),
        ?Profiler\ProfilerInterface $profiler = null
    );

    public function getDriver(): Driver\DriverInterface;
    public function getPlatform(): Platform\PlatformInterface;
    public function getProfiler(): ?Profiler\ProfilerInterface;
    public function getQueryResultSetPrototype(): ResultSet\ResultSetInterface;
    public function getCurrentSchema(): string|false;

    public function query(
        string $sql,
        ParameterContainer|array|string $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?ResultSet\ResultSetInterface $resultPrototype = null
    ): Driver\StatementInterface|ResultSet\ResultSet|Driver\ResultInterface;

    public function createStatement(
        ?string $initialSql = null,
        ParameterContainer|array|null $initialParameters = null
    ): Driver\StatementInterface;
}
```

### Constructor Parameters

- **`$driver`**: A `DriverInterface` implementation from a driver package (e.g., `PhpDb\Mysql\Driver\Mysql`)
- **`$platform`**: A `PlatformInterface` implementation for SQL dialect handling
- **`$queryResultSetPrototype`** (optional): Custom `ResultSetInterface` for query results
- **`$profiler`** (optional): A profiler for query logging and performance analysis

## Query Preparation

By default, `PhpDb\Adapter\Adapter::query()` prefers that you use
"preparation" as a means for processing SQL statements. This generally means
that you will supply a SQL statement containing placeholders for the values, and
separately provide substitutions for those placeholders:

### Query with Prepared Statement

```php
$adapter->query('SELECT * FROM `artist` WHERE `id` = ?', [5]);
```

The above example will go through the following steps:

1. Create a new `Statement` object
2. Prepare the array `[5]` into a `ParameterContainer` if necessary
3. Inject the `ParameterContainer` into the `Statement` object
4. Execute the `Statement` object, producing a `Result` object
5. Check the `Result` object to check if the supplied SQL was a result set
   producing statement:
    - If the query produced a result set, clone the `ResultSet` prototype,
      inject the `Result` as its datasource, and return the new `ResultSet`
      instance
    - Otherwise, return the `Result`

## Query Execution

In some cases, you have to execute statements directly without preparation. One
possible reason for doing so would be to execute a DDL statement, as most
extensions and RDBMS systems are incapable of preparing such statements.

To execute a query without the preparation step, pass a flag as
the second argument indicating execution is required:

### Executing DDL Statement Without Preparation

```php
$adapter->query(
    'ALTER TABLE ADD INDEX(`foo_index`) ON (`foo_column`)',
    Adapter::QUERY_MODE_EXECUTE
);
```

The primary difference to notice is that you must provide the
`Adapter::QUERY_MODE_EXECUTE` (execute) flag as the second parameter.

## Creating Statements

While `query()` is highly useful for one-off and quick querying of a database
via the `Adapter`, it generally makes more sense to create a statement and
interact with it directly, so that you have greater control over the
prepare-then-execute workflow:

### Creating and Executing a Statement

```php
$statement = $adapter->createStatement($sql, $optionalParameters);
$result    = $statement->execute();
```

## Using the Driver Object

The `Driver` object is the primary place where `PhpDb\Adapter\Adapter`
implements the connection level abstraction specific to a given extension. Each
driver is composed of three objects:

- A connection: `PhpDb\Adapter\Driver\ConnectionInterface`
- A statement: `PhpDb\Adapter\Driver\StatementInterface`
- A result: `PhpDb\Adapter\Driver\ResultInterface`

### DriverInterface

### Driver Interface Definition

```php
namespace PhpDb\Adapter\Driver;

interface DriverInterface
{
    public const PARAMETERIZATION_POSITIONAL = 'positional';
    public const PARAMETERIZATION_NAMED = 'named';
    public const NAME_FORMAT_CAMELCASE = 'camelCase';
    public const NAME_FORMAT_NATURAL = 'natural';

    public function getDatabasePlatformName(string $nameFormat = self::NAME_FORMAT_CAMELCASE): string;
    public function checkEnvironment(): bool;
    public function getConnection(): ConnectionInterface;
    public function createStatement($sqlOrResource = null): StatementInterface;
    public function createResult($resource): ResultInterface;
    public function getPrepareType(): string;
    public function formatParameterName(string $name, ?string $type = null): string;
    public function getLastGeneratedValue(): int|string|bool|null;
}
```

From this `DriverInterface`, you can:

- Determine the name of the platform this driver supports (useful for choosing
  the proper platform object)
- Check that the environment can support this driver
- Return the `Connection` instance
- Create a `Statement` instance which is optionally seeded by an SQL statement
  (this will generally be a clone of a prototypical statement object)
- Create a `Result` object which is optionally seeded by a statement resource
  (this will generally be a clone of a prototypical result object)
- Format parameter names; this is important to distinguish the difference
  between the various ways parameters are named between extensions
- Retrieve the overall last generated value (such as an auto-increment value)

### StatementInterface

### Statement Interface Definition

```php
namespace PhpDb\Adapter\Driver;

interface StatementInterface extends StatementContainerInterface
{
    public function getResource(): mixed;
    public function prepare(?string $sql = null): void;
    public function isPrepared(): bool;
    public function execute(?array|ParameterContainer $parameters = null): ResultInterface;

    /** Inherited from StatementContainerInterface */
    public function setSql(string $sql): void;
    public function getSql(): string;
    public function setParameterContainer(ParameterContainer $parameterContainer): void;
    public function getParameterContainer(): ParameterContainer;
}
```

### ResultInterface

### Result Interface Definition

```php
namespace PhpDb\Adapter\Driver;

use Countable;
use Iterator;

interface ResultInterface extends Countable, Iterator
{
    public function buffer(): void;
    public function isQueryResult(): bool;
    public function getAffectedRows(): int;
    public function getGeneratedValue(): mixed;
    public function getResource(): mixed;
    public function getFieldCount(): int;
}
```

## Using The Platform Object

The `Platform` object provides an API to assist in crafting queries in a way
that is specific to the SQL implementation of a particular vendor. The object
handles nuances such as how identifiers or values are quoted, or what the
identifier separator character is:

### Platform Interface Definition

```php
namespace PhpDb\Adapter\Platform;

interface PlatformInterface
{
    public function getName(): string;
    public function getQuoteIdentifierSymbol(): string;
    public function quoteIdentifier(string $identifier): string;
    public function quoteIdentifierChain(array|string $identifierChain): string;
    public function getQuoteValueSymbol(): string;
    public function quoteValue(string $value): string;
    public function quoteTrustedValue(int|float|string|bool $value): ?string;
    public function quoteValueList(array|string $valueList): string;
    public function getIdentifierSeparator(): string;
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string;
}
```

While you can directly instantiate a `Platform` object, generally speaking, it
is easier to get the proper `Platform` instance from the configured adapter (by
default the `Platform` type will match the underlying driver implementation):

### Getting Platform from Adapter

```php
$platform = $adapter->getPlatform();

// or
$platform = $adapter->platform; // magic property access
```

### Platform Usage Examples

### Quoting Identifiers and Values

```php
$platform = $adapter->getPlatform();

// "first_name"
echo $platform->quoteIdentifier('first_name');

// "
echo $platform->getQuoteIdentifierSymbol();

// "schema"."mytable"
echo $platform->quoteIdentifierChain(['schema', 'mytable']);

// '
echo $platform->getQuoteValueSymbol();

// 'myvalue'
echo $platform->quoteValue('myvalue');

// 'value', 'Foo O\'Bar'
echo $platform->quoteValueList(['value', "Foo O'Bar"]);

// .
echo $platform->getIdentifierSeparator();

// "foo" as "bar"
echo $platform->quoteIdentifierInFragment('foo as bar');

// additionally, with some safe words:
// ("foo"."bar" = "boo"."baz")
echo $platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '=']);
```

## Using The Parameter Container

The `ParameterContainer` object is a container for the various parameters that
need to be passed into a `Statement` object to fulfill all the various
parameterized parts of the SQL statement:

### ParameterContainer Class Interface

```php
namespace PhpDb\Adapter;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;

class ParameterContainer implements Iterator, ArrayAccess, Countable
{
    public function __construct(array $data = []);

    /** Methods to interact with values */
    public function offsetExists(string|int $name): bool;
    public function offsetGet(string|int $name): mixed;
    public function offsetSetReference(string|int $name, string|int $from): void;
    public function offsetSet(string|int $name, mixed $value, mixed $errata = null, int $maxLength = null): void;
    public function offsetUnset(string|int $name): void;

    /** Set values from array (will reset first) */
    public function setFromArray(array $data): ParameterContainer;

    /** Methods to interact with value errata */
    public function offsetSetErrata(string|int $name, mixed $errata): void;
    public function offsetGetErrata(string|int $name): mixed;
    public function offsetHasErrata(string|int $name): bool;
    public function offsetUnsetErrata(string|int $name): void;

    /** Errata only iterator */
    public function getErrataIterator(): ArrayIterator;

    /** Get array with named keys */
    public function getNamedArray(): array;

    /** Get array with int keys, ordered by position */
    public function getPositionalArray(): array;

    /** Iterator methods */
    public function count(): int;
    public function current(): mixed;
    public function next(): void;
    public function key(): string|int;
    public function valid(): bool;
    public function rewind(): void;

    /** Merge existing array of parameters with existing parameters */
    public function merge(array $parameters): ParameterContainer;
}
```

### Parameter Type Binding

In addition to handling parameter names and values, the container will assist in
tracking parameter types for PHP type to SQL type handling:

### Setting Parameter Without Type

```php
$container->offsetSet('limit', 5);
```

To bind as an integer, pass the `ParameterContainer::TYPE_INTEGER` constant as
the 3rd parameter:

### Setting Parameter with Type Binding

```php
$container->offsetSet('limit', 5, $container::TYPE_INTEGER);
```

This will ensure that if the underlying driver supports typing of bound
parameters, that this translated information will also be passed along to the
actual PHP database driver.

## Driver Features

Drivers can provide optional features through the `DriverFeatureProviderInterface`:

### DriverFeatureProviderInterface Definition

```php
namespace PhpDb\Adapter\Driver\Feature;

interface DriverFeatureProviderInterface
{
    /** @param DriverFeatureInterface[] $features */
    public function addFeatures(array $features): DriverFeatureProviderInterface;
    public function addFeature(DriverFeatureInterface $feature): DriverFeatureProviderInterface;
    public function getFeature(string $name): DriverFeatureInterface|false;
}
```

Features allow driver packages to extend functionality without modifying the core
interfaces. Each driver package may define its own features specific to the
database platform.

## Profiling

The adapter supports profiling through the `ProfilerInterface`:

### Setting Up a Profiler

```php
use PhpDb\Adapter\Profiler\Profiler;

$profiler = new Profiler();
$adapter = new Adapter($driver, $platform, profiler: $profiler);

// Execute queries...
$result = $adapter->query('SELECT * FROM users');

// Get profiler data
$profiles = $profiler->getProfiles();
```

## Complete Example

Creating a driver, a vendor-portable query, and preparing and iterating the result:

### Full Workflow Example with Adapter

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Mysql\Driver\Mysql;
use PhpDb\Mysql\Platform\Mysql as MysqlPlatform;

$driver = new Mysql([
    'database' => 'my_database',
    'username' => 'my_user',
    'password' => 'my_password',
]);

$adapter = new Adapter($driver, new MysqlPlatform());

$qi = function ($name) use ($adapter) {
    return $adapter->platform->quoteIdentifier($name);
};
$fp = function ($name) use ($adapter) {
    return $adapter->driver->formatParameterName($name);
};

$sql = 'UPDATE ' . $qi('artist')
    . ' SET ' . $qi('name') . ' = ' . $fp('name')
    . ' WHERE ' . $qi('id') . ' = ' . $fp('id');

$statement = $adapter->query($sql);

$parameters = [
    'name' => 'Updated Artist',
    'id'   => 1,
];

$statement->execute($parameters);

// DATA UPDATED, NOW CHECK
$statement = $adapter->query(
    'SELECT * FROM '
    . $qi('artist')
    . ' WHERE id = ' . $fp('id')
);

$results = $statement->execute(['id' => 1]);

$row = $results->current();
$name = $row['name'];
```