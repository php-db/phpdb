# RDBMS Metadata

`PhpDb\Metadata` is a sub-component of laminas-db that makes it possible to get
metadata information about tables, columns, constraints, triggers, and other
information from a database in a standardized way. The primary interface for
`Metadata` is:

## MetadataInterface Definition

```php
namespace PhpDb\Metadata;

interface MetadataInterface
{
    public function getSchemas() : string[];

    public function getTableNames(?string $schema = null, bool $includeViews = false) : string[];
    public function getTables(?string $schema = null, bool $includeViews = false) : Object\TableObject[];
    public function getTable(string $tableName, ?string $schema = null) : Object\TableObject|Object\ViewObject;

    public function getViewNames(?string $schema = null) : string[];
    public function getViews(?string $schema = null) : Object\ViewObject[];
    public function getView(string $viewName, ?string $schema = null) : Object\ViewObject|Object\TableObject;

    public function getColumnNames(string $table, ?string $schema = null) : string[];
    public function getColumns(string $table, ?string $schema = null) : Object\ColumnObject[];
    public function getColumn(string $columnName, string $table, ?string $schema = null) : Object\ColumnObject;

    public function getConstraints(string $table, ?string $schema = null) : Object\ConstraintObject[];
    public function getConstraint(string $constraintName, string $table, ?string $schema = null) : Object\ConstraintObject;
    public function getConstraintKeys(string $constraint, string $table, ?string $schema = null) : Object\ConstraintKeyObject[];

    public function getTriggerNames(?string $schema = null) : string[];
    public function getTriggers(?string $schema = null) : Object\TriggerObject[];
    public function getTrigger(string $triggerName, ?string $schema = null) : Object\TriggerObject;
}
```

## Basic Usage

### Instantiating Metadata

The `PhpDb\Metadata` component uses platform-specific implementations to retrieve
metadata from your database. The metadata instance is typically created through
dependency injection or directly with an adapter:

```php title="Creating Metadata from an Adapter"
use PhpDb\Adapter\Adapter;
use PhpDb\Metadata\Source\Factory as MetadataSourceFactory;

$adapter = new Adapter($config);
$metadata = MetadataSourceFactory::createSourceFromAdapter($adapter);
```

### Retrieving Metadata from a DI Container

Alternatively, when using a dependency injection container:

```php
use PhpDb\Metadata\MetadataInterface;

$metadata = $container->get(MetadataInterface::class);
```

In most cases, information will come from querying the `INFORMATION_SCHEMA`
tables for the currently accessible schema.

### Understanding Return Types

The `get*Names()` methods return arrays of strings:

```php title="Getting Names of Database Objects"
$tableNames = $metadata->getTableNames();
$columnNames = $metadata->getColumnNames('users');
$schemas = $metadata->getSchemas();
```

### Getting Object Instances

The other methods return value objects specific to the type queried:

```php
$table = $metadata->getTable('users');       // Returns TableObject or ViewObject
$column = $metadata->getColumn('id', 'users'); // Returns ColumnObject
$constraint = $metadata->getConstraint('PRIMARY', 'users'); // Returns ConstraintObject
```

Note that `getTable()` and `getView()` can return either `TableObject` or
`ViewObject` depending on whether the database object is a table or a view.

```php title="Basic Example"
use PhpDb\Metadata\Source\Factory as MetadataSourceFactory;

$adapter = new Adapter($config);
$metadata = MetadataSourceFactory::createSourceFromAdapter($adapter);

$table = $metadata->getTable('users');

foreach ($table->getColumns() as $column) {
    $nullable = $column->isNullable() ? 'NULL' : 'NOT NULL';
    $default = $column->getColumnDefault();

    printf(
        "%s %s %s%s\n",
        $column->getName(),
        strtoupper($column->getDataType()),
        $nullable,
        $default ? " DEFAULT {$default}" : ''
    );
}
```

Example output:

```text
id INT NOT NULL
username VARCHAR NOT NULL
email VARCHAR NOT NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
bio TEXT NULL
```

### Inspecting Table Constraints

Inspecting constraints:

```php
$constraints = $metadata->getConstraints('orders');

foreach ($constraints as $constraint) {
    if ($constraint->isPrimaryKey()) {
        printf("PRIMARY KEY (%s)\n", implode(', ', $constraint->getColumns()));
    }

    if ($constraint->isForeignKey()) {
        printf(
            "FOREIGN KEY %s (%s) REFERENCES %s (%s)\n",
            $constraint->getName(),
            implode(', ', $constraint->getColumns()),
            $constraint->getReferencedTableName(),
            implode(', ', $constraint->getReferencedColumns())
        );
    }
}
```

Example output:

```text
PRIMARY KEY (id)
FOREIGN KEY fk_orders_customers (customer_id) REFERENCES customers (id)
FOREIGN KEY fk_orders_products (product_id) REFERENCES products (id)
```

## Advanced Usage

### Working with Schemas

The `getSchemas()` method returns all available schema names in the database:

```php title="Listing All Schemas and Their Tables"
$schemas = $metadata->getSchemas();
foreach ($schemas as $schema) {
    $tables = $metadata->getTableNames($schema);
    printf("Schema: %s\n  Tables: %s\n", $schema, implode(', ', $tables));
}
```

When the `$schema` parameter is `null`, the metadata component uses the current
default schema from the adapter. You can explicitly specify a schema for any method:

```php title="Specifying a Schema Explicitly"
$tables = $metadata->getTableNames('production');
$columns = $metadata->getColumns('users', 'production');
$constraints = $metadata->getConstraints('users', 'production');
```

### Working with Views

Retrieve all views in the current schema:

```php title="Retrieving View Information"
$viewNames = $metadata->getViewNames();
foreach ($viewNames as $viewName) {
    $view = $metadata->getView($viewName);
    printf(
        "View: %s\n  Updatable: %s\n  Check Option: %s\n  Definition: %s\n",
        $view->getName(),
        $view->isUpdatable() ? 'Yes' : 'No',
        $view->getCheckOption() ?? 'NONE',
        $view->getViewDefinition()
    );
}
```

### Distinguishing Between Tables and Views

Distinguishing between tables and views:

```php
$table = $metadata->getTable('users');

if ($table instanceof \PhpDb\Metadata\Object\ViewObject) {
    printf("View: %s\nDefinition: %s\n", $table->getName(), $table->getViewDefinition());
} else {
    printf("Table: %s\n", $table->getName());
}
```

### Including Views in Table Listings

Include views when getting table names:

```php
$allTables = $metadata->getTableNames(null, true);
```

### Working with Triggers

Retrieve all triggers and their details:

```php title="Retrieving Trigger Information"
$triggers = $metadata->getTriggers();
foreach ($triggers as $trigger) {
    printf(
        "%s (%s %s on %s)\n  Statement: %s\n",
        $trigger->getName(),
        $trigger->getActionTiming(),
        $trigger->getEventManipulation(),
        $trigger->getEventObjectTable(),
        $trigger->getActionStatement()
    );
}
```

The `getEventManipulation()` returns the trigger event:

- `INSERT` - Trigger fires on INSERT operations
- `UPDATE` - Trigger fires on UPDATE operations
- `DELETE` - Trigger fires on DELETE operations

The `getActionTiming()` returns when the trigger fires:

- `BEFORE` - Executes before the triggering statement
- `AFTER` - Executes after the triggering statement

### Analyzing Foreign Key Relationships

Get detailed foreign key information using `getConstraintKeys()`:

```php title="Examining Foreign Key Details"
$constraints = $metadata->getConstraints('orders');
$foreignKeys = array_filter($constraints, fn($c) => $c->isForeignKey());

foreach ($foreignKeys as $constraint) {
    printf("Foreign Key: %s\n", $constraint->getName());

    $keys = $metadata->getConstraintKeys($constraint->getName(), 'orders');
    foreach ($keys as $key) {
        printf(
            "  %s -> %s.%s\n    ON UPDATE: %s\n    ON DELETE: %s\n",
            $key->getColumnName(),
            $key->getReferencedTableName(),
            $key->getReferencedColumnName(),
            $key->getForeignKeyUpdateRule(),
            $key->getForeignKeyDeleteRule()
        );
    }
}
```

Outputs:

```text
Foreign Key: fk_orders_customers
  customer_id -> customers.id
    ON UPDATE: CASCADE
    ON DELETE: RESTRICT
Foreign Key: fk_orders_products
  product_id -> products.id
    ON UPDATE: CASCADE
    ON DELETE: NO ACTION
```

### Column Type Information

Examine column types and their properties:

```php title="Examining Column Data Types"
$column = $metadata->getColumn('price', 'products');

if ($column->getDataType() === 'decimal') {
    $precision = $column->getNumericPrecision();
    $scale = $column->getNumericScale();
    echo "Column is DECIMAL($precision, $scale)" . PHP_EOL;
}

if ($column->getDataType() === 'varchar') {
    $maxLength = $column->getCharacterMaximumLength();
    echo "Column is VARCHAR($maxLength)" . PHP_EOL;
}

if ($column->getDataType() === 'int') {
    $unsigned = $column->isNumericUnsigned() ? 'UNSIGNED' : '';
    echo "Column is INT $unsigned" . PHP_EOL;
}
```

### Checking Column Nullability and Defaults

Check column nullability and defaults:

```php
$column = $metadata->getColumn('email', 'users');

echo 'Nullable: ' . ($column->isNullable() ? 'YES' : 'NO') . PHP_EOL;
echo 'Default: ' . ($column->getColumnDefault() ?? 'NULL') . PHP_EOL;
echo 'Position: ' . $column->getOrdinalPosition() . PHP_EOL;
```

### The Errata System

The `ColumnObject` includes an errata system for storing database-specific
metadata not covered by the standard properties:

```php title="Using the Errata System"
$columns = $metadata->getColumns('users');
foreach ($columns as $column) {
    if ($column->getErrata('auto_increment')) {
        echo $column->getName() . ' is AUTO_INCREMENT' . PHP_EOL;
    }

    $comment = $column->getErrata('comment');
    if ($comment) {
        echo $column->getName() . ': ' . $comment . PHP_EOL;
    }
}
```

### Setting Errata on Column Objects

You can also set errata when programmatically creating column objects:

```php
$column->setErrata('auto_increment', true);
$column->setErrata('comment', 'Primary key for users table');
$column->setErrata('collation', 'utf8mb4_unicode_ci');
```

### Retrieving All Errata at Once

Get all errata at once:

```php
$erratas = $column->getErratas();
foreach ($erratas as $key => $value) {
    echo "$key: $value" . PHP_EOL;
}
```

### Fluent Interface Pattern

All setter methods on value objects return `static`, enabling method chaining:

```php title="Using Method Chaining with Value Objects"
$column = new ColumnObject('id', 'users');
$column->setDataType('int')
    ->setIsNullable(false)
    ->setNumericUnsigned(true)
    ->setErrata('auto_increment', true);

$constraint = new ConstraintObject('fk_user_role', 'users');
$constraint->setType('FOREIGN KEY')
    ->setColumns(['role_id'])
    ->setReferencedTableName('roles')
    ->setReferencedColumns(['id'])
    ->setUpdateRule('CASCADE')
    ->setDeleteRule('RESTRICT');
```
