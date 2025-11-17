# RDBMS Metadata

`PhpDb\Metadata` is as sub-component of laminas-db that makes it possible to get
metadata information about tables, columns, constraints, triggers, and other
information from a database in a standardized way. The primary interface for
`Metadata` is:

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

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Metadata\Source\Factory as MetadataSourceFactory;

$adapter = new Adapter($config);
$metadata = MetadataSourceFactory::createSourceFromAdapter($adapter);
```

Alternatively, when using a dependency injection container:

```php
use PhpDb\Metadata\MetadataInterface;

$metadata = $container->get(MetadataInterface::class);
```

In most cases, information will come from querying the `INFORMATION_SCHEMA`
tables for the currently accessible schema.

### Understanding Return Types

The `get*Names()` methods return arrays of strings:

```php
$tableNames = $metadata->getTableNames();
$columnNames = $metadata->getColumnNames('users');
$schemas = $metadata->getSchemas();
```

The other methods return value objects specific to the type queried:

```php
$table = $metadata->getTable('users');       // Returns TableObject or ViewObject
$column = $metadata->getColumn('id', 'users'); // Returns ColumnObject
$constraint = $metadata->getConstraint('PRIMARY', 'users'); // Returns ConstraintObject
```

Note that `getTable()` and `getView()` can return either `TableObject` or
`ViewObject` depending on whether the database object is a table or a view.

### Basic Example

```php
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

```
id INT NOT NULL
username VARCHAR NOT NULL
email VARCHAR NOT NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
bio TEXT NULL
```

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

```
PRIMARY KEY (id)
FOREIGN KEY fk_orders_customers (customer_id) REFERENCES customers (id)
FOREIGN KEY fk_orders_products (product_id) REFERENCES products (id)
```

## Metadata value objects

Metadata returns value objects that provide an interface to help developers
better explore the metadata. Below is the API for the various value objects:

### TableObject

```php
class PhpDb\Metadata\Object\TableObject
{
    public function __construct($name);
    public function setColumns(array $columns);
    public function getColumns();
    public function setConstraints($constraints);
    public function getConstraints();
    public function setName($name);
    public function getName();
}
```

### ColumnObject

```php
class PhpDb\Metadata\Object\ColumnObject
{
    public function __construct($name, $tableName, $schemaName = null);
    public function setName($name);
    public function getName();
    public function getTableName();
    public function setTableName($tableName);
    public function setSchemaName($schemaName);
    public function getSchemaName();
    public function getOrdinalPosition();
    public function setOrdinalPosition($ordinalPosition);
    public function getColumnDefault();
    public function setColumnDefault($columnDefault);
    public function getIsNullable();
    public function setIsNullable($isNullable);
    public function isNullable();
    public function getDataType();
    public function setDataType($dataType);
    public function getCharacterMaximumLength();
    public function setCharacterMaximumLength($characterMaximumLength);
    public function getCharacterOctetLength();
    public function setCharacterOctetLength($characterOctetLength);
    public function getNumericPrecision();
    public function setNumericPrecision($numericPrecision);
    public function getNumericScale();
    public function setNumericScale($numericScale);
    public function getNumericUnsigned();
    public function setNumericUnsigned($numericUnsigned);
    public function isNumericUnsigned();
    public function getErratas();
    public function setErratas(array $erratas);
    public function getErrata($errataName);
    public function setErrata($errataName, $errataValue);
}
```

### ConstraintObject

```php
class PhpDb\Metadata\Object\ConstraintObject
{
    public function __construct($name, $tableName, $schemaName = null);
    public function setName($name);
    public function getName();
    public function setSchemaName($schemaName);
    public function getSchemaName();
    public function getTableName();
    public function setTableName($tableName);
    public function setType($type);
    public function getType();
    public function hasColumns();
    public function getColumns();
    public function setColumns(array $columns);
    public function getReferencedTableSchema();
    public function setReferencedTableSchema($referencedTableSchema);
    public function getReferencedTableName();
    public function setReferencedTableName($referencedTableName);
    public function getReferencedColumns();
    public function setReferencedColumns(array $referencedColumns);
    public function getMatchOption();
    public function setMatchOption($matchOption);
    public function getUpdateRule();
    public function setUpdateRule($updateRule);
    public function getDeleteRule();
    public function setDeleteRule($deleteRule);
    public function getCheckClause();
    public function setCheckClause($checkClause);
    public function isPrimaryKey();
    public function isUnique();
    public function isForeignKey();
    public function isCheck();

}
```

### ViewObject

The `ViewObject` extends `AbstractTableObject` and represents database views. It
includes all methods from `TableObject` plus view-specific properties:

```php
class PhpDb\Metadata\Object\ViewObject extends AbstractTableObject
{
    public function __construct(?string $name = null);
    public function setName(string $name): void;
    public function getName(): ?string;
    public function setColumns(array $columns): void;
    public function getColumns(): ?array;
    public function setConstraints(array $constraints): void;
    public function getConstraints(): ?array;

    public function getViewDefinition(): ?string;
    public function setViewDefinition(?string $viewDefinition): static;

    public function getCheckOption(): ?string;
    public function setCheckOption(?string $checkOption): static;

    public function getIsUpdatable(): ?bool;
    public function isUpdatable(): ?bool;
    public function setIsUpdatable(?bool $isUpdatable): static;
}
```

The `getViewDefinition()` method returns the SQL that creates the view:

```php
$view = $metadata->getView('active_users');
echo $view->getViewDefinition();
```

Outputs:

```sql
SELECT id, name, email FROM users WHERE status = 'active'
```

The `getCheckOption()` returns the view's check option:

- `CASCADED` - Checks for updatability cascade to underlying views
- `LOCAL` - Only checks this view for updatability
- `NONE` - No check option specified

The `isUpdatable()` method (alias for `getIsUpdatable()`) indicates whether the
view supports INSERT, UPDATE, or DELETE operations.

### ConstraintKeyObject

The `ConstraintKeyObject` provides detailed information about individual columns
participating in constraints, particularly useful for foreign key relationships:

```php
class PhpDb\Metadata\Object\ConstraintKeyObject
{
    public const FK_CASCADE = 'CASCADE';
    public const FK_SET_NULL = 'SET NULL';
    public const FK_NO_ACTION = 'NO ACTION';
    public const FK_RESTRICT = 'RESTRICT';
    public const FK_SET_DEFAULT = 'SET DEFAULT';

    public function __construct(string $column);

    public function getColumnName(): string;
    public function setColumnName(string $columnName): static;

    public function getOrdinalPosition(): ?int;
    public function setOrdinalPosition(int $ordinalPosition): static;

    public function getPositionInUniqueConstraint(): ?bool;
    public function setPositionInUniqueConstraint(bool $positionInUniqueConstraint): static;

    public function getReferencedTableSchema(): ?string;
    public function setReferencedTableSchema(string $referencedTableSchema): static;

    public function getReferencedTableName(): ?string;
    public function setReferencedTableName(string $referencedTableName): static;

    public function getReferencedColumnName(): ?string;
    public function setReferencedColumnName(string $referencedColumnName): static;

    public function getForeignKeyUpdateRule(): ?string;
    public function setForeignKeyUpdateRule(string $foreignKeyUpdateRule): void;

    public function getForeignKeyDeleteRule(): ?string;
    public function setForeignKeyDeleteRule(string $foreignKeyDeleteRule): void;
}
```

Constraint keys are retrieved using `getConstraintKeys()`:

```php
$keys = $metadata->getConstraintKeys('fk_orders_customers', 'orders');
foreach ($keys as $key) {
    echo $key->getColumnName() . ' -> '
         . $key->getReferencedTableName() . '.'
         . $key->getReferencedColumnName() . PHP_EOL;
    echo '  ON UPDATE: ' . $key->getForeignKeyUpdateRule() . PHP_EOL;
    echo '  ON DELETE: ' . $key->getForeignKeyDeleteRule() . PHP_EOL;
}
```

Outputs:

```
customer_id -> customers.id
  ON UPDATE: CASCADE
  ON DELETE: RESTRICT
```

### TriggerObject

```php
class PhpDb\Metadata\Object\TriggerObject
{
    public function getName();
    public function setName($name);
    public function getEventManipulation();
    public function setEventManipulation($eventManipulation);
    public function getEventObjectCatalog();
    public function setEventObjectCatalog($eventObjectCatalog);
    public function getEventObjectSchema();
    public function setEventObjectSchema($eventObjectSchema);
    public function getEventObjectTable();
    public function setEventObjectTable($eventObjectTable);
    public function getActionOrder();
    public function setActionOrder($actionOrder);
    public function getActionCondition();
    public function setActionCondition($actionCondition);
    public function getActionStatement();
    public function setActionStatement($actionStatement);
    public function getActionOrientation();
    public function setActionOrientation($actionOrientation);
    public function getActionTiming();
    public function setActionTiming($actionTiming);
    public function getActionReferenceOldTable();
    public function setActionReferenceOldTable($actionReferenceOldTable);
    public function getActionReferenceNewTable();
    public function setActionReferenceNewTable($actionReferenceNewTable);
    public function getActionReferenceOldRow();
    public function setActionReferenceOldRow($actionReferenceOldRow);
    public function getActionReferenceNewRow();
    public function setActionReferenceNewRow($actionReferenceNewRow);
    public function getCreated();
    public function setCreated($created);
}
```

## Advanced Usage

### Working with Schemas

The `getSchemas()` method returns all available schema names in the database:

```php
$schemas = $metadata->getSchemas();
foreach ($schemas as $schema) {
    $tables = $metadata->getTableNames($schema);
    printf("Schema: %s\n  Tables: %s\n", $schema, implode(', ', $tables));
}
```

When the `$schema` parameter is `null`, the metadata component uses the current
default schema from the adapter. You can explicitly specify a schema for any method:

```php
$tables = $metadata->getTableNames('production');
$columns = $metadata->getColumns('users', 'production');
$constraints = $metadata->getConstraints('users', 'production');
```

### Working with Views

Retrieve all views in the current schema:

```php
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

Distinguishing between tables and views:

```php
$table = $metadata->getTable('users');

if ($table instanceof \PhpDb\Metadata\Object\ViewObject) {
    printf("View: %s\nDefinition: %s\n", $table->getName(), $table->getViewDefinition());
} else {
    printf("Table: %s\n", $table->getName());
}
```

Include views when getting table names:

```php
$allTables = $metadata->getTableNames(null, true);
```

### Working with Triggers

Retrieve all triggers and their details:

```php
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

```php
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

```
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

```php
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

```php
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

You can also set errata when programmatically creating column objects:

```php
$column->setErrata('auto_increment', true);
$column->setErrata('comment', 'Primary key for users table');
$column->setErrata('collation', 'utf8mb4_unicode_ci');
```

Get all errata at once:

```php
$erratas = $column->getErratas();
foreach ($erratas as $key => $value) {
    echo "$key: $value" . PHP_EOL;
}
```

### Fluent Interface Pattern

All setter methods on value objects return `static`, enabling method chaining:

```php
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

## Error Handling and Exceptions

All metadata methods throw the base PHP `\Exception` when the requested object is
not found. Note that while PhpDb has its own exception hierarchy
(`PhpDb\Exception\ExceptionInterface`), the Metadata component currently uses the
base Exception class.

### Catching Metadata Exceptions

```php
use Exception;

try {
    $table = $metadata->getTable('nonexistent_table');
} catch (Exception $e) {
    printf("Table not found: %s\n", $e->getMessage());
}
```

### Common Exception Scenarios

**Table not found:**

```php
try {
    $table = $metadata->getTable('invalid_table');
} catch (Exception $e) {
    // Message: Table "invalid_table" does not exist
}
```

**View not found:**

```php
try {
    $view = $metadata->getView('invalid_view');
} catch (Exception $e) {
    // Message: View "invalid_view" does not exist
}
```

**Column not found:**

```php
try {
    $column = $metadata->getColumn('invalid_column', 'users');
} catch (Exception $e) {
    // Message: A column by that name was not found.
}
```

**Constraint not found:**

```php
try {
    $constraint = $metadata->getConstraint('invalid_constraint', 'users');
} catch (Exception $e) {
    // Message: Cannot find a constraint by that name in this table
}
```

**Trigger not found:**

```php
try {
    $trigger = $metadata->getTrigger('invalid_trigger');
} catch (Exception $e) {
    // Message: Trigger "invalid_trigger" does not exist
}
```

**Unsupported table type:**

```php
try {
    $table = $metadata->getTable('user_view');
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'unsupported type')) {
        // This object exists but is not a supported table type
    }
}
```

### Best Practices for Exception Handling

Check for existence before accessing metadata:

```php
$tableNames = $metadata->getTableNames();
if (! in_array('users', $tableNames, true)) {
    throw new RuntimeException('Required table "users" does not exist');
}

$table = $metadata->getTable('users');
```

Catch and log exceptions for better debugging:

```php
try {
    $column = $metadata->getColumn('email', 'users');
} catch (Exception $e) {
    $logger->error('Failed to retrieve column metadata', [
        'column' => 'email',
        'table' => 'users',
        'error' => $e->getMessage(),
    ]);
    throw $e;
}
```

## Common Patterns and Best Practices

### Finding All Tables with a Specific Column

```php
function findTablesWithColumn(MetadataInterface $metadata, string $columnName): array
{
    $tables = [];
    foreach ($metadata->getTableNames() as $tableName) {
        $columnNames = $metadata->getColumnNames($tableName);
        if (in_array($columnName, $columnNames, true)) {
            $tables[] = $tableName;
        }
    }
    return $tables;
}

$tablesWithUserId = findTablesWithColumn($metadata, 'user_id');
```

### Discovering Foreign Key Relationships

```php
function getForeignKeyRelationships(MetadataInterface $metadata, string $tableName): array
{
    $relationships = [];
    $constraints = $metadata->getConstraints($tableName);

    foreach ($constraints as $constraint) {
        if (! $constraint->isForeignKey()) {
            continue;
        }

        $relationships[] = [
            'constraint' => $constraint->getName(),
            'columns' => $constraint->getColumns(),
            'references' => $constraint->getReferencedTableName(),
            'referenced_columns' => $constraint->getReferencedColumns(),
            'on_update' => $constraint->getUpdateRule(),
            'on_delete' => $constraint->getDeleteRule(),
        ];
    }

    return $relationships;
}
```

### Generating Schema Documentation

```php
function generateTableDocumentation(MetadataInterface $metadata, string $tableName): string
{
    $table = $metadata->getTable($tableName);
    $doc = "# Table: $tableName\n\n";

    $doc .= "## Columns\n\n";
    $doc .= "| Column | Type | Nullable | Default |\n";
    $doc .= "|--------|------|----------|--------|\n";

    foreach ($table->getColumns() as $column) {
        $type = $column->getDataType();
        if ($column->getCharacterMaximumLength()) {
            $type .= '(' . $column->getCharacterMaximumLength() . ')';
        } elseif ($column->getNumericPrecision()) {
            $type .= '(' . $column->getNumericPrecision();
            if ($column->getNumericScale()) {
                $type .= ',' . $column->getNumericScale();
            }
            $type .= ')';
        }

        $nullable = $column->isNullable() ? 'YES' : 'NO';
        $default = $column->getColumnDefault() ?? 'NULL';

        $doc .= "| {$column->getName()} | $type | $nullable | $default |\n";
    }

    $doc .= "\n## Constraints\n\n";
    $constraints = $metadata->getConstraints($tableName);

    foreach ($constraints as $constraint) {
        $doc .= "- **{$constraint->getName()}** ({$constraint->getType()})\n";
        if ($constraint->hasColumns()) {
            $doc .= "  - Columns: " . implode(', ', $constraint->getColumns()) . "\n";
        }
        if ($constraint->isForeignKey()) {
            $doc .= "  - References: {$constraint->getReferencedTableName()}";
            $doc .= "(" . implode(', ', $constraint->getReferencedColumns()) . ")\n";
            $doc .= "  - ON UPDATE: {$constraint->getUpdateRule()}\n";
            $doc .= "  - ON DELETE: {$constraint->getDeleteRule()}\n";
        }
    }

    return $doc;
}
```

### Comparing Schemas Across Environments

```php
function compareTables(
    MetadataInterface $metadata1,
    MetadataInterface $metadata2,
    string $tableName
): array {
    $differences = [];

    $columns1 = $metadata1->getColumnNames($tableName);
    $columns2 = $metadata2->getColumnNames($tableName);

    $missing = array_diff($columns1, $columns2);
    if ($missing) {
        $differences['missing_columns'] = $missing;
    }

    $extra = array_diff($columns2, $columns1);
    if ($extra) {
        $differences['extra_columns'] = $extra;
    }

    return $differences;
}
```

## Troubleshooting

### Table Not Found Errors

Always check if a table exists before trying to access it:

```php
$tableNames = $metadata->getTableNames();
if (in_array('users', $tableNames, true)) {
    $table = $metadata->getTable('users');
} else {
    echo 'Table does not exist';
}
```

### Performance with Large Schemas

When working with databases that have hundreds of tables, use `get*Names()`
methods instead of retrieving full objects:

```php
$tableNames = $metadata->getTableNames();
foreach ($tableNames as $tableName) {
    $columnNames = $metadata->getColumnNames($tableName);
}
```

This is more efficient than:

```php
$tables = $metadata->getTables();
foreach ($tables as $table) {
    $columns = $table->getColumns();
}
```

### Schema Permission Issues

If you encounter errors accessing certain tables or schemas, verify database
user permissions:

```php
try {
    $tables = $metadata->getTableNames('restricted_schema');
} catch (Exception $e) {
    echo 'Access denied or schema does not exist';
}
```

### Caching Metadata

The metadata component queries the database each time a method is called. For
better performance in production, consider caching the results:

```php
$cache = $container->get('cache');
$cacheKey = 'metadata_tables';

$tables = $cache->get($cacheKey);
if ($tables === null) {
    $tables = $metadata->getTables();
    $cache->set($cacheKey, $tables, 3600);
}
```

## Platform-Specific Behavior

### MySQL

- View definitions include `SELECT` statement exactly as stored
- Supports `AUTO_INCREMENT` in column errata
- Trigger support is comprehensive with full INFORMATION_SCHEMA access
- Check constraints available in MySQL 8.0+

### PostgreSQL

- Schema support is robust, multiple schemas are common
- View `check_option` is well-supported
- Detailed trigger information including conditions
- Sequence information available in column errata

### SQLite

- Limited schema support (single default schema)
- View definitions may be formatted differently
- Trigger support varies by SQLite version
- Foreign key enforcement must be enabled separately

### SQL Server

- Schema support is robust with `dbo` as default schema
- View definitions may include schema qualifiers
- Trigger information may have platform-specific fields
- Constraint types may include platform-specific values
