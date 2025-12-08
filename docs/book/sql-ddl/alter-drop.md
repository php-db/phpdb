# Modifying and Dropping Tables

## AlterTable

The `AlterTable` class represents an `ALTER TABLE` statement. It provides methods to modify existing table structures.

```php title="Basic AlterTable Creation"
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\TableIdentifier;

// Simple
$alter = new AlterTable('users');

// With schema
$alter = new AlterTable(new TableIdentifier('users', 'public'));

// Set after construction
$alter = new AlterTable();
$alter->setTable('users');
```

### Adding Columns

Add new columns to an existing table:

```php
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column;

$alter = new AlterTable('users');

// Add a single column
$alter->addColumn(new Column\Varchar('phone', 20));

// Add multiple columns
$alter->addColumn(new Column\Varchar('city', 100));
$alter->addColumn(new Column\Varchar('country', 2));
```

### SQL Output for Adding Columns

**Generated SQL:**

```sql
ALTER TABLE "users"
ADD COLUMN "phone" VARCHAR(20) NOT NULL,
ADD COLUMN "city" VARCHAR(100) NOT NULL,
ADD COLUMN "country" VARCHAR(2) NOT NULL
```

### Changing Columns

Modify existing column definitions:

```php
$alter = new AlterTable('users');

// Change column type or properties
$alter->changeColumn('name', new Column\Varchar('name', 500));
$alter->changeColumn('age', new Column\Integer('age'));

// Rename and change at the same time
$alter->changeColumn('name', new Column\Varchar('full_name', 200));
```

### SQL Output for Changing Columns

**Generated SQL:**

```sql
ALTER TABLE "users"
CHANGE COLUMN "name" "full_name" VARCHAR(200) NOT NULL
```

### Dropping Columns

Remove columns from a table:

```php
$alter = new AlterTable('users');

$alter->dropColumn('old_field');
$alter->dropColumn('deprecated_column');
```

### SQL Output for Dropping Columns

**Generated SQL:**

```sql
ALTER TABLE "users"
DROP COLUMN "old_field",
DROP COLUMN "deprecated_column"
```

### Adding Constraints

Add table constraints:

```php
use PhpDb\Sql\Ddl\Constraint;

$alter = new AlterTable('users');

// Add primary key
$alter->addConstraint(new Constraint\PrimaryKey('id'));

// Add unique constraint
$alter->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));

// Add foreign key
$alter->addConstraint(new Constraint\ForeignKey(
    'fk_user_department',
    'department_id',
    'departments',
    'id',
    'SET NULL',  // ON DELETE
    'CASCADE'    // ON UPDATE
));

// Add check constraint
$alter->addConstraint(new Constraint\Check('age >= 18', 'check_adult'));
```

### Dropping Constraints

Remove constraints from a table:

```php
$alter = new AlterTable('users');

$alter->dropConstraint('old_unique_key');
$alter->dropConstraint('fk_old_relation');
```

### SQL Output for Dropping Constraints

**Generated SQL:**

```sql
ALTER TABLE "users"
DROP CONSTRAINT "old_unique_key",
DROP CONSTRAINT "fk_old_relation"
```

### Adding Indexes

Add indexes to improve query performance:

```php
use PhpDb\Sql\Ddl\Index\Index;

$alter = new AlterTable('products');

// Simple index
$alter->addConstraint(new Index('name', 'idx_product_name'));

// Composite index
$alter->addConstraint(new Index(['category', 'price'], 'idx_category_price'));

// Index with column length specifications
$alter->addConstraint(new Index(
    ['title', 'description'],
    'idx_search',
    [50, 100] // Index first 50 chars of title, 100 of description
));
```

### Dropping Indexes

Remove indexes from a table:

```php
$alter = new AlterTable('products');

$alter->dropIndex('idx_old_search');
$alter->dropIndex('idx_deprecated');
```

### SQL Output for Dropping Indexes

**Generated SQL:**

```sql
ALTER TABLE "products"
DROP INDEX "idx_old_search",
DROP INDEX "idx_deprecated"
```

### Complex AlterTable Example

Combine multiple operations in a single statement:

```php
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Index\Index;

$alter = new AlterTable('users');

// Add new columns
$alter->addColumn(new Column\Varchar('email', 255));
$alter->addColumn(new Column\Varchar('phone', 20));

$updated = new Column\Timestamp('updated_at');
$updated->setDefault('CURRENT_TIMESTAMP');
$updated->setOption('on_update', true);
$alter->addColumn($updated);

// Modify existing columns
$alter->changeColumn('name', new Column\Varchar('full_name', 200));

// Drop old columns
$alter->dropColumn('old_field');
$alter->dropColumn('deprecated_field');

// Add constraints
$alter->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));
$alter->addConstraint(new Constraint\ForeignKey(
    'fk_user_role',
    'role_id',
    'roles',
    'id',
    'CASCADE',
    'CASCADE'
));

// Drop old constraints
$alter->dropConstraint('old_constraint');

// Add index
$alter->addConstraint(new Index(['full_name', 'email'], 'idx_user_search'));

// Drop old index
$alter->dropIndex('idx_old_search');

// Execute
$sql = new Sql($adapter);
$adapter->query(
    $sql->buildSqlString($alter),
    $adapter::QUERY_MODE_EXECUTE
);
```

## DropTable

The `DropTable` class represents a `DROP TABLE` statement.

```php title="Basic Drop Table"
use PhpDb\Sql\Ddl\DropTable;

// Simple
$drop = new DropTable('old_table');

// Execute
$sql = new Sql($adapter);
$adapter->query(
    $sql->buildSqlString($drop),
    $adapter::QUERY_MODE_EXECUTE
);
```

### SQL Output for Basic Drop Table

**Generated SQL:**

```sql
DROP TABLE "old_table"
```

```php title="Schema-Qualified Drop"
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\TableIdentifier;

$drop = new DropTable(new TableIdentifier('users', 'archive'));
```

### SQL Output for Schema-Qualified Drop

**Generated SQL:**

```sql
DROP TABLE "archive"."users"
```

### Dropping Multiple Tables

Execute multiple drop statements:

```php
$tables = ['temp_table1', 'temp_table2', 'old_cache'];

foreach ($tables as $tableName) {
    $drop = new DropTable($tableName);
    $adapter->query(
        $sql->buildSqlString($drop),
        $adapter::QUERY_MODE_EXECUTE
    );
}
```

## Platform-Specific Considerations

### Current Status

**Important:** Platform-specific DDL decorators have been **removed during refactoring**. The decorator infrastructure exists in the codebase but specific platform implementations (MySQL, SQL Server, Oracle, SQLite) have been deprecated and removed.

### What This Means

1. **Platform specialization is handled at the Adapter Platform level**, not the SQL DDL level
2. **DDL objects are platform-agnostic** - they define the structure, and the platform renders it appropriately
3. **The decorator system can be used manually** if needed via `setTypeDecorator()`, but this is advanced usage

### Platform-Agnostic Approach

The DDL abstraction is designed to work across platforms without modification:

```php title="Example of Platform-Agnostic DDL Code"
// This code works on MySQL, PostgreSQL, SQL Server, SQLite, etc.
$table = new CreateTable('users');
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));

// The platform adapter handles rendering differences:
// - MySQL: CREATE TABLE `users` (`id` INT NOT NULL, `name` VARCHAR(255) NOT NULL)
// - PostgreSQL: CREATE TABLE "users" ("id" INTEGER NOT NULL, "name" VARCHAR(255) NOT NULL)
// - SQL Server: CREATE TABLE [users] ([id] INT NOT NULL, [name] VARCHAR(255) NOT NULL)
```

### Platform-Specific Options

Use column options for platform-specific features:

```php title="Using Platform-Specific Column Options"
// MySQL AUTO_INCREMENT
$id = new Column\Integer('id');
$id->setOption('AUTO_INCREMENT', true);

// PostgreSQL/SQL Server IDENTITY
$id = new Column\Integer('id');
$id->setOption('identity', true);

// MySQL UNSIGNED
$count = new Column\Integer('count');
$count->setOption('unsigned', true);
```

**Note:** Not all options work on all platforms. Test your DDL against your target database.

### Platform Detection

```php title="Detecting Database Platform at Runtime"
// Check platform before using platform-specific options
$platformName = $adapter->getPlatform()->getName();

if ($platformName === 'MySQL') {
    $id->setOption('AUTO_INCREMENT', true);
} elseif (in_array($platformName, ['PostgreSQL', 'SqlServer'])) {
    $id->setOption('identity', true);
}
```

## Inspecting DDL Objects

Use `getRawState()` to inspect the internal configuration of DDL objects:

```php title="Using getRawState() to Inspect DDL Configuration"
$table = new CreateTable('users');
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));
$table->addConstraint(new Constraint\PrimaryKey('id'));

// Get the internal state
$state = $table->getRawState();
print_r($state);

/* Output:
Array(
    [table] => users
    [temporary] => false
    [columns] => Array(...)
    [constraints] => Array(...)
)
*/
```

This is useful for:

- Debugging DDL object configuration
- Testing DDL generation
- Introspection and analysis tools

## Working with Table Identifiers

Use `TableIdentifier` for schema-qualified table references:

```php title="Creating and Using Table Identifiers"
use PhpDb\Sql\TableIdentifier;

// Table in default schema
$identifier = new TableIdentifier('users');

// Table in specific schema
$identifier = new TableIdentifier('users', 'public');
$identifier = new TableIdentifier('audit_log', 'audit');

// Use in DDL objects
$table = new CreateTable(new TableIdentifier('users', 'auth'));
$alter = new AlterTable(new TableIdentifier('products', 'inventory'));
$drop = new DropTable(new TableIdentifier('temp', 'scratch'));

// In foreign keys (schema.table syntax)
$fk = new ForeignKey(
    'fk_user_role',
    'role_id',
    new TableIdentifier('roles', 'auth'), // Referenced table with schema
    'id'
);
```

## Nullable and Default Values

### Setting Nullable

```php title="Configuring Nullable Columns"
// NOT NULL (default for most types)
$column = new Column\Varchar('email', 255);
$column->setNullable(false);

// Allow NULL
$column = new Column\Varchar('middle_name', 100);
$column->setNullable(true);

// Check if nullable
if ($column->isNullable()) {
    // ...
}
```

**Note:** Boolean columns cannot be made nullable:

```php
$column = new Column\Boolean('is_active');
$column->setNullable(true); // Has no effect - still NOT NULL
```

### Setting Default Values

```php title="Configuring Default Column Values"
// String default
$column = new Column\Varchar('status', 20);
$column->setDefault('pending');

// Numeric default
$column = new Column\Integer('count');
$column->setDefault(0);

// SQL expression default
$column = new Column\Timestamp('created_at');
$column->setDefault('CURRENT_TIMESTAMP');

// NULL default (requires nullable column)
$column = new Column\Varchar('notes', 255);
$column->setNullable(true);
$column->setDefault(null);

// Get default value
$default = $column->getDefault();
```

## Fluent Interface Patterns

All DDL objects support method chaining for cleaner, more readable code.

### Chaining Column Configuration

```php title="Example of Fluent Column Configuration"
$column = (new Column\Varchar('email', 255))
    ->setNullable(false)
    ->setDefault('user@example.com')
    ->setOption('comment', 'User email address')
    ->addConstraint(new Constraint\UniqueKey());

$table->addColumn($column);
```

### Chaining Table Construction

```php title="Example of Fluent Table Construction"
$table = (new CreateTable('users'))
    ->addColumn(
        (new Column\Integer('id'))
            ->setOption('AUTO_INCREMENT', true)
            ->addConstraint(new Constraint\PrimaryKey())
    )
    ->addColumn(
        (new Column\Varchar('username', 50))
            ->setNullable(false)
    )
    ->addColumn(
        (new Column\Varchar('email', 255))
            ->setNullable(false)
    )
    ->addConstraint(new Constraint\UniqueKey('username', 'unique_username'))
    ->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));
```
