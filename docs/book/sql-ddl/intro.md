# DDL Abstraction Overview

`PhpDb\Sql\Ddl` provides object-oriented abstraction for DDL (Data Definition Language) statements. Create, alter, and drop tables using PHP objects instead of raw SQL, with automatic platform-specific SQL generation.

## Basic Workflow

The typical workflow for using DDL abstraction:

1. **Create a DDL object** (CreateTable, AlterTable, or DropTable)
2. **Configure the object** (add columns, constraints, etc.)
3. **Generate SQL** using `Sql::buildSqlString()`
4. **Execute** using `Adapter::query()` with `QUERY_MODE_EXECUTE`

```php title="Creating and Executing a Simple Table"
use PhpDb\Sql\Sql;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\Column;

// Assuming $adapter exists
$sql = new Sql($adapter);

// Create a DDL object
$table = new CreateTable('users');
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));

// Execute
$adapter->query(
    $sql->buildSqlString($table),
    $adapter::QUERY_MODE_EXECUTE
);
```

## Creating Tables

The `CreateTable` class represents a `CREATE TABLE` statement. You can build complex table definitions using a fluent, object-oriented interface.

```php title="Basic Table Creation"
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\Column;

// Simple table
$table = new CreateTable('users');
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));
```

### SQL Output for Basic Table

**Generated SQL:**

```sql
CREATE TABLE "users" (
    "id" INTEGER NOT NULL,
    "name" VARCHAR(255) NOT NULL
)
```

### Setting the Table Name

You can set the table name during construction or after instantiation:

```php
// During construction
$table = new CreateTable('products');

// After instantiation
$table = new CreateTable();
$table->setTable('products');
```

### Schema-Qualified Tables

Use `TableIdentifier` to create tables in a specific schema:

```php
use PhpDb\Sql\TableIdentifier;

// Create table in the "public" schema
$table = new CreateTable(new TableIdentifier('users', 'public'));
```

### SQL Output for Schema-Qualified Table

**Generated SQL:**

```sql
CREATE TABLE "public"."users" (...)
```

### Temporary Tables

Create temporary tables by passing `true` as the second parameter:

```php
$table = new CreateTable('temp_data', true);

// Or use the setter
$table = new CreateTable('temp_data');
$table->setTemporary(true);
```

### SQL Output for Temporary Table

**Generated SQL:**

```sql
CREATE TEMPORARY TABLE "temp_data" (...)
```

### Adding Columns

Columns are added using the `addColumn()` method with column type objects:

```php
use PhpDb\Sql\Ddl\Column;

$table = new CreateTable('products');

// Add various column types
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));
$table->addColumn(new Column\Text('description'));
$table->addColumn(new Column\Decimal('price', 10, 2));
$table->addColumn(new Column\Boolean('is_active'));
$table->addColumn(new Column\Timestamp('created_at'));
```

### Adding Constraints

Table-level constraints are added using `addConstraint()`:

```php
use PhpDb\Sql\Ddl\Constraint;

// Primary key
$table->addConstraint(new Constraint\PrimaryKey('id'));

// Unique constraint
$table->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));

// Foreign key
$table->addConstraint(new Constraint\ForeignKey(
    'fk_user_role',    // Constraint name
    'role_id',         // Column in this table
    'roles',           // Referenced table
    'id',              // Referenced column
    'CASCADE',         // ON DELETE rule
    'CASCADE'          // ON UPDATE rule
));

// Check constraint
$table->addConstraint(new Constraint\Check('price > 0', 'check_positive_price'));
```

### Column-Level Constraints

Columns can have constraints attached directly:

```php
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;

// Create a primary key column
$id = new Column\Integer('id');
$id->addConstraint(new Constraint\PrimaryKey());
$table->addColumn($id);
```

### SQL Output for Column-Level Constraint

**Generated SQL:**

```sql
"id" INTEGER NOT NULL PRIMARY KEY
```

### Fluent Interface Pattern

All DDL objects support method chaining for cleaner code:

```php
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;

$table = (new CreateTable('users'))
    ->addColumn(
        (new Column\Integer('id'))
            ->setNullable(false)
            ->addConstraint(new Constraint\PrimaryKey())
    )
    ->addColumn(
        (new Column\Varchar('email', 255))
            ->setNullable(false)
    )
    ->addConstraint(new Constraint\UniqueKey('email', 'unique_user_email'));
```

```php title="Complete Example: User Table"
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Index\Index;

$table = new CreateTable('users');

// Auto-increment primary key
$id = new Column\Integer('id');
$id->setOption('AUTO_INCREMENT', true);
$id->addConstraint(new Constraint\PrimaryKey());
$table->addColumn($id);

// Basic columns
$table->addColumn(new Column\Varchar('username', 50));
$table->addColumn(new Column\Varchar('email', 255));
$table->addColumn(new Column\Varchar('password_hash', 255));

// Optional columns
$bio = new Column\Text('bio');
$bio->setNullable(true);
$table->addColumn($bio);

// Boolean (always NOT NULL)
$table->addColumn(new Column\Boolean('is_active'));

// Timestamps
$created = new Column\Timestamp('created_at');
$created->setDefault('CURRENT_TIMESTAMP');
$table->addColumn($created);

$updated = new Column\Timestamp('updated_at');
$updated->setDefault('CURRENT_TIMESTAMP');
$updated->setOption('on_update', true);
$table->addColumn($updated);

// Constraints
$table->addConstraint(new Constraint\UniqueKey('username', 'unique_username'));
$table->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));
$table->addConstraint(new Constraint\Check('email LIKE "%@%"', 'check_email_format'));

// Index for searches
$table->addConstraint(new Index(['username', 'email'], 'idx_user_search'));

// Execute
$sql = new Sql($adapter);
$adapter->query(
    $sql->buildSqlString($table),
    $adapter::QUERY_MODE_EXECUTE
);
```
