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

- **BigInteger**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Binary**: `$name`, `$length=null`, `$nullable=false`, `$default=null`,
  `$options=[]`
- **Blob**: `$name`, `$length=null`, `$nullable=false`, `$default=null`,
  `$options=[]`
- **Boolean**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Char**: `$name`, `$length=null`, `$nullable=false`, `$default=null`,
  `$options=[]`
- **Column** (generic): `$name=''`, `$nullable=false`, `$default=null`,
  `$options=[]`
- **Date**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Datetime**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Decimal**: `$name`, `$digits=null`, `$decimal=null`, `$nullable`,
  `$default`, `$options`
- **Floating**: `$name`, `$digits=null`, `$decimal=null`, `$nullable`,
  `$default`, `$options`
- **Integer**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Text**: `$name`, `$length=null`, `$nullable=false`, `$default=null`,
  `$options=[]`
- **Time**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Timestamp**: `$name`, `$nullable=false`, `$default=null`, `$options=[]`
- **Varbinary**: `$name`, `$length=null`, `$nullable=false`, `$default=null`,
  `$options=[]`
- **Varchar**: `$name`, `$length=null`, `$nullable=false`, `$default=null`,
  `$options=[]`

Each of the above types can be utilized in any place that accepts a `Column\ColumnInterface`
instance. Currently, this is primarily in `CreateTable::addColumn()` and `AlterTable`'s
`addColumn()` and `changeColumn()` methods.

## Currently Supported Constraint Types

These types exist in the `PhpDb\Sql\Ddl\Constraint` namespace. Data types
must implement `PhpDb\Sql\Ddl\Constraint\ConstraintInterface`.

In alphabetical order:

- **Check**: `$expression`, `$name`
- **ForeignKey**: `$name`, `$columns`, `$refTable`, `$refColumn`,
  `$onDelete`, `$onUpdate`
- **PrimaryKey**: `$columns=null`, `$name=null`
- **UniqueKey**: `$columns=null`, `$name=null`

Each of the above types can be utilized in any place that accepts a
`Constraint\ConstraintInterface` instance. Currently, this is primarily in
`CreateTable::addConstraint()` and `AlterTable::addConstraint()`.
