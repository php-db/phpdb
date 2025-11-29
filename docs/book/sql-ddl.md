# DDL Abstraction

`PhpDb\Sql\Ddl` is a sub-component of `PhpDb\Sql` allowing creation of DDL
(Data Definition Language) SQL statements. When combined with a platform
specific `PhpDb\Sql\Sql` object, DDL objects are capable of producing
platform-specific `CREATE TABLE` statements, with specialized data types,
constraints, and indexes for a database/schema.

The following platforms have platform specializations for DDL:

- MySQL
- All databases compatible with ANSI SQL92

## Creating Tables

Like `PhpDb\Sql` objects, each statement type is represented by a class. For
example, `CREATE TABLE` is modeled by the `CreateTable` class; this is likewise
the same for `ALTER TABLE` (as `AlterTable`), and `DROP TABLE` (as
`DropTable`). You can create instances using a number of approaches:

```php
use PhpDb\Sql\Ddl;
use PhpDb\Sql\TableIdentifier;

$table = new Ddl\CreateTable();

// With a table name:
$table = new Ddl\CreateTable('bar');

// With a schema name "foo":
$table = new Ddl\CreateTable(new TableIdentifier('bar', 'foo'));

// Optionally, as a temporary table:
$table = new Ddl\CreateTable('bar', true);
```

You can also set the table after instantiation:

```php
$table->setTable('bar');
```

Currently, columns are added by creating a column object (described in the
[data type table below](#currently-supported-data-types)):

```php
use PhpDb\Sql\Ddl\Column;

$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));
```

Beyond adding columns to a table, you may also add constraints:

```php
use PhpDb\Sql\Ddl\Constraint;

$table->addConstraint(new Constraint\PrimaryKey('id'));
$table->addConstraint(
    new Constraint\UniqueKey(['name', 'foo'], 'my_unique_key')
);
```

You can also use the `AUTO_INCREMENT` attribute for MySQL:

```php
use PhpDb\Sql\Ddl\Column;

$column = new Column\Integer('id');
$column->setOption('AUTO_INCREMENT', true);
```

## Altering Tables

Similar to `CreateTable`, you may also use `AlterTable` instances:

```php
use PhpDb\Sql\Ddl;
use PhpDb\Sql\TableIdentifier;

$table = new Ddl\AlterTable();

// With a table name:
$table = new Ddl\AlterTable('bar');

// With a schema name "foo":
$table = new Ddl\AlterTable(new TableIdentifier('bar', 'foo'));
```

The primary difference between a `CreateTable` and `AlterTable` is that the
`AlterTable` takes into account that the table and its assets already exist.
Therefore, while you still have `addColumn()` and `addConstraint()`, you will
also have the ability to *alter* existing columns:

```php
use PhpDb\Sql\Ddl\Column;

$table->changeColumn('name', new Column\Varchar('new_name', 50));
```

You may also *drop* existing columns or constraints:

```php
$table->dropColumn('foo');
$table->dropConstraint('my_index');
```

## Dropping Tables

To drop a table, create a `DropTable` instance:

```php
use PhpDb\Sql\Ddl;
use PhpDb\Sql\TableIdentifier;

// With a table name:
$drop = new Ddl\DropTable('bar');

// With a schema name "foo":
$drop = new Ddl\DropTable(new TableIdentifier('bar', 'foo'));
```

## Executing DDL Statements

After a DDL statement object has been created and configured, at some point you
will need to execute the statement. This requires an `Adapter` instance and a
properly seeded `Sql` instance.

The workflow looks something like this, with `$ddl` being a `CreateTable`,
`AlterTable`, or `DropTable` instance:

```php
use PhpDb\Sql\Sql;

// Existence of $adapter is assumed.
$sql = new Sql($adapter);

$adapter->query(
    $sql->buildSqlString($ddl),
    $adapter::QUERY_MODE_EXECUTE
);
```

By passing the `$ddl` object through the `$sql` instance's
`buildSqlString()` method, we ensure that any platform specific
specializations/modifications are utilized to create a platform specific SQL
statement.

Next, using the constant `PhpDb\Adapter\Adapter::QUERY_MODE_EXECUTE` ensures
that the SQL statement is not prepared, as most DDL statements on most
platforms cannot be prepared, only executed.

## Currently Supported Data Types

These types exist in the `PhpDb\Sql\Ddl\Column` namespace. Data types must
implement `PhpDb\Sql\Ddl\Column\ColumnInterface`.

In alphabetical order:

| Type             | Arguments For Construction                                                                             |
|------------------|--------------------------------------------------------------------------------------------------------|
| BigInteger       | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Binary           | `string $name`, `?int $length = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Blob             | `string $name`, `?int $length = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Boolean          | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Char             | `string $name`, `?int $length = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Column (generic) | `string $name = ''`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`          |
| Date             | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Datetime         | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Decimal          | `string $name`, `?int $digits = null`, `?int $decimal = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Floating         | `string $name`, `?int $digits = null`, `?int $decimal = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Integer          | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Text             | `string $name`, `?int $length = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Time             | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Timestamp        | `string $name`, `bool $nullable = false`, `mixed $default = null`, `array $options = []`               |
| Varbinary        | `string $name`, `?int $length = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |
| Varchar          | `string $name`, `?int $length = null`, `bool $nullable = false`, `mixed $default = null`, `array $options = []` |

Each of the above types can be utilized in any place that accepts a `Column\ColumnInterface`
instance. Currently, this is primarily in `CreateTable::addColumn()` and `AlterTable`'s
`addColumn()` and `changeColumn()` methods.

## Currently Supported Constraint Types

These types exist in the `PhpDb\Sql\Ddl\Constraint` namespace. Data types
must implement `PhpDb\Sql\Ddl\Constraint\ConstraintInterface`.

In alphabetical order:

| Type       | Arguments For Construction                                                                                                                    |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------|
| Check      | `string\|ExpressionInterface $expression`, `?string $name`                                                                                    |
| ForeignKey | `string $name`, `string\|array $columns`, `string $referenceTable`, `array\|string\|null $referenceColumn`, `?string $onDeleteRule = null`, `?string $onUpdateRule = null` |
| PrimaryKey | `null\|array\|string $columns = null`, `?string $name = null`                                                                                 |
| UniqueKey  | `null\|array\|string $columns = null`, `?string $name = null`                                                                                 |

Each of the above types can be utilized in any place that accepts a
`Constraint\ConstraintInterface` instance. Currently, this is primarily in
`CreateTable::addConstraint()` and `AlterTable::addConstraint()`.
