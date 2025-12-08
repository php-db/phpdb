# SQL Abstraction

`PhpDb\Sql` provides an object-oriented API for building platform-specific SQL queries. It produces either a prepared `Statement` with `ParameterContainer`, or a raw SQL string for direct execution. Requires an `Adapter` for platform-specific SQL generation.

## Quick Start

The `PhpDb\Sql\Sql` class creates the four primary DML statement types: `Select`, `Insert`, `Update`, and `Delete`.

### Creating SQL Statement Objects

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter);
$select = $sql->select(); // returns a PhpDb\Sql\Select instance
$insert = $sql->insert(); // returns a PhpDb\Sql\Insert instance
$update = $sql->update(); // returns a PhpDb\Sql\Update instance
$delete = $sql->delete(); // returns a PhpDb\Sql\Delete instance
```

As a developer, you can now interact with these objects, as described in the
sections below, to customize each query. Once they have been populated with
values, they are ready to either be prepared or executed.

### Preparing a Statement

To prepare (using a Select object):

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter);
$select = $sql->select();
$select->from('foo');
$select->where(['id' => 2]);

$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();
```

### Executing a Query Directly

To execute (using a Select object)

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter);
$select = $sql->select();
$select->from('foo');
$select->where(['id' => 2]);

$selectString = $sql->buildSqlString($select);
$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
```

`PhpDb\\Sql\\Sql` objects can also be bound to a particular table so that in
obtaining a `Select`, `Insert`, `Update`, or `Delete` instance, the object will be
seeded with the table:

### Binding to a Default Table

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter, 'foo');
$select = $sql->select();
$select->where(['id' => 2]); // $select already has from('foo') applied
```

## Common Interfaces for SQL Implementations

Each of these objects implements the following two interfaces:

### PreparableSqlInterface and SqlInterface

```php
interface PreparableSqlInterface
{
     public function prepareStatement(
         Adapter $adapter,
         StatementInterface $statement
     ) : void;
}

interface SqlInterface
{
     public function getSqlString(PlatformInterface $adapterPlatform = null) : string;
}
```

Use these functions to produce either (a) a prepared statement, or (b) a string
to execute.

## SQL Arguments and Argument Types

`PhpDb\Sql` provides individual `Argument\<type>` types as well as an
`Argument` factory class and an `ArgumentType` enum for type-safe
specification of SQL values. This provides a modern, object-oriented
alternative to using raw values or the legacy type constants.

The `ArgumentType` enum defines six types, each backed by its corresponding class:

- `Identifier` - For column names, table names, and other identifiers that
  should be quoted
- `Identifiers` - For arrays of identifiers (e.g., multi-column IN predicates)
- `Value` - For values that should be parameterized or properly escaped
  (default)
- `Values` - For arrays of values (e.g., IN clauses)
- `Literal` - For literal SQL fragments that should not be quoted or escaped
- `Select` - For subqueries (Expression or SqlInterface objects)

All argument classes are `readonly` and implement `ArgumentInterface`:

### Using Argument Factory and Classes

```php
use PhpDb\Sql\Argument;

// Using the Argument factory class (recommended)
$valueArg = Argument::value(123);             // Value type
$identifierArg = Argument::identifier('id');  // Identifier type
$literalArg = Argument::literal('NOW()');     // Literal SQL
$valuesArg = Argument::values([1, 2, 3]);     // Multiple values
$identifiersArg = Argument::identifiers(['col1', 'col2']); // Multiple identifiers

// Direct instantiation is preferred
$arg = new Argument\Identifier('column_name');
$arg = new Argument\Value(123);
$arg = new Argument\Literal('NOW()');
$arg = new Argument\Values([1, 2, 3]);
```

The `Argument` classes are particularly useful when working with expressions
where you need to explicitly control how values are treated:

### Type-Safe Expression Arguments

```php
use PhpDb\Sql\Argument;
use PhpDb\Sql\Expression;

// With Argument classes - explicit and type-safe
$expression = new Expression(
    'CONCAT(?, ?, ?)',
    [
        new Argument\Identifier('column1'),
        new Argument\Value('-'),
        new Argument\Identifier('column2')
    ]
);
```

Scalar values passed directly to `Expression` are automatically wrapped:

- Scalars become `Argument\Value`
- Arrays become `Argument\Values`
- `ExpressionInterface` instances become `Argument\Select`

> ### Literals
>
> `PhpDb\Sql` makes the distinction that literals will not have any parameters
> that need interpolating, while `Expression` objects *might* have parameters
> that need interpolating. In cases where there are parameters in an `Expression`,
> `PhpDb\Sql\AbstractSql` will do its best to identify placeholders when the
> `Expression` is processed during statement creation. In short, if you don't
> have parameters, use `Literal` objects`.

## Working with the Sql Factory Class

The `Sql` class serves as a factory for creating SQL statement objects and provides methods for preparing and building SQL strings.

### Instantiating the Sql Factory

```php
use PhpDb\Sql\Sql;

$sql = new Sql($adapter);
$sql = new Sql($adapter, 'defaultTable');
```

### Factory Methods

```php
$select = $sql->select();
$select = $sql->select('users');

$insert = $sql->insert();
$insert = $sql->insert('users');

$update = $sql->update();
$update = $sql->update('users');

$delete = $sql->delete();
$delete = $sql->delete('users');
```

### Using a Default Table with Factory Methods

When a default table is set on the Sql instance, it will be used for all created statements unless overridden:

```php
$sql = new Sql($adapter, 'users');
$select = $sql->select();
$insert = $sql->insert();
```

### Preparing and Executing Queries

The recommended approach for executing queries is to prepare them first:

```php
$select = $sql->select('users')->where(['status' => 'active']);
$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();
```

This approach:

- Uses parameter binding for security against SQL injection
- Allows the database to cache query plans
- Is the preferred method for production code

### Building SQL String for Debugging

For debugging or special cases, you can build the SQL string directly:

```php
$select = $sql->select('users')->where(['id' => 5]);
$sqlString = $sql->buildSqlString($select);
```

Note: Direct string building bypasses parameter binding. Use with caution and never with user input.

### Getting the SQL Platform

```php
$platform = $sql->getSqlPlatform();
```

The platform object handles database-specific SQL generation and can be used for custom query building.

## TableIdentifier

The `TableIdentifier` class provides a type-safe way to reference tables,
especially when working with schemas or databases.

### Creating and Using TableIdentifier

```php
use PhpDb\Sql\TableIdentifier;

$table = new TableIdentifier('users', 'production');

$tableName = $table->getTable();
$schemaName = $table->getSchema();

[$table, $schema] = $table->getTableAndSchema();
```

### TableIdentifier in SELECT Queries

Usage in SQL objects:

```php
$select = new Select(new TableIdentifier('orders', 'ecommerce'));

$select->join(
    new TableIdentifier('customers', 'crm'),
    'orders.customerId = customers.id'
);
```

Produces:

```sql
SELECT * FROM "ecommerce"."orders"
INNER JOIN "crm"."customers" ON orders.customerId = customers.id
```

### TableIdentifier with Table Aliases

With aliases:

```php
$select->from(['o' => new TableIdentifier('orders', 'sales')])
    ->join(
        ['c' => new TableIdentifier('customers', 'crm')],
        'o.customerId = c.id'
    );
```
