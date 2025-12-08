# Advanced DDL Features

## Error Handling

### DDL Error Behavior

**Important:** DDL objects themselves do **not throw exceptions** during construction or configuration. They are designed to build up state without validation.

Errors typically occur during:
1. **SQL Generation** - When `buildSqlString()` is called
2. **Execution** - When the adapter executes the DDL statement

### Exception Types

DDL-related operations can throw:

```php
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Adapter\Exception\InvalidQueryException;
```

### Common Error Scenarios

#### 1. Empty Expression

```php
use PhpDb\Sql\Expression;

try {
    $expr = new Expression(''); // Throws InvalidArgumentException
} catch (\PhpDb\Sql\Exception\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
    // Error: Supplied expression must not be an empty string.
}
```

#### 2. SQL Execution Errors

```php
use PhpDb\Sql\Sql;
use PhpDb\Sql\Ddl\CreateTable;

$table = new CreateTable('users');
// ... configure table ...

$sql = new Sql($adapter);

try {
    $adapter->query(
        $sql->buildSqlString($table),
        $adapter::QUERY_MODE_EXECUTE
    );
} catch (\Exception $e) {
    // Catch execution errors (syntax errors, constraint violations, etc.)
    echo "DDL execution failed: " . $e->getMessage();
}
```

#### 3. Platform-Specific Errors

Different platforms may reject different DDL constructs:

```php
// SQLite doesn't support DROP CONSTRAINT
$alter = new AlterTable('users');
$alter->dropConstraint('unique_email');

try {
    $adapter->query($sql->buildSqlString($alter), $adapter::QUERY_MODE_EXECUTE);
} catch (\Exception $e) {
    // SQLite will throw an error: ALTER TABLE syntax does not support DROP CONSTRAINT
    echo "Platform error: " . $e->getMessage();
}
```

### Error Handling Best Practices

#### 1. Wrap DDL Execution in Try-Catch

```php
function createTable($adapter, $table) {
    $sql = new Sql($adapter);

    try {
        $adapter->query(
            $sql->buildSqlString($table),
            $adapter::QUERY_MODE_EXECUTE
        );
        return true;
    } catch (\PhpDb\Adapter\Exception\InvalidQueryException $e) {
        // SQL syntax or execution error
        error_log("DDL execution failed: " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        // General error
        error_log("Unexpected error: " . $e->getMessage());
        return false;
    }
}
```

#### 2. Validate Platform Capabilities

```php
function alterTable($adapter, $alterTable) {
    $platformName = $adapter->getPlatform()->getName();

    // Check if platform supports ALTER TABLE ... DROP CONSTRAINT
    if ($platformName === 'SQLite' && hasDropConstraint($alterTable)) {
        throw new \RuntimeException(
            'SQLite does not support DROP CONSTRAINT in ALTER TABLE'
        );
    }

    // Proceed with execution
    $sql = new Sql($adapter);
    $adapter->query($sql->buildSqlString($alterTable), $adapter::QUERY_MODE_EXECUTE);
}
```

#### 3. Transaction Wrapping

```php
use PhpDb\Adapter\Adapter;

function executeMigration($adapter, array $ddlObjects) {
    $connection = $adapter->getDriver()->getConnection();

    try {
        $connection->beginTransaction();

        $sql = new Sql($adapter);
        foreach ($ddlObjects as $ddl) {
            $adapter->query(
                $sql->buildSqlString($ddl),
                Adapter::QUERY_MODE_EXECUTE
            );
        }

        $connection->commit();
        return true;

    } catch (\Exception $e) {
        $connection->rollback();
        error_log("Migration failed: " . $e->getMessage());
        return false;
    }
}
```

### Debugging DDL Issues

#### Use getRawState() for Inspection

```php
$table = new CreateTable('users');
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));

// Inspect the DDL object state
$state = $table->getRawState();
print_r($state);

/*
Array(
    [table] => users
    [isTemporary] => false
    [columns] => Array(
        [0] => PhpDb\Sql\Ddl\Column\Integer Object
        [1] => PhpDb\Sql\Ddl\Column\Varchar Object
    )
    [constraints] => Array()
)
*/
```

#### Generate SQL Without Execution

```php
$sql = new Sql($adapter);

// Generate the SQL string to see what will be executed
$sqlString = $sql->buildSqlString($table);
echo $sqlString . "\n";

// Review before executing
if (confirmExecution($sqlString)) {
    $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
}
```

#### Log DDL Statements

```php
use PhpDb\Adapter\Adapter;

function executeDdl($adapter, $ddl, $logger) {
    $sql = new Sql($adapter);
    $sqlString = $sql->buildSqlString($ddl);

    // Log before execution
    $logger->info("Executing DDL: " . $sqlString);

    try {
        $adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);
        $logger->info("DDL executed successfully");
    } catch (\Exception $e) {
        $logger->error("DDL execution failed: " . $e->getMessage());
        throw $e;
    }
}
```

## Best Practices

### Naming Conventions

#### Table Names

```php
// Use plural, lowercase, snake_case
new CreateTable('users');           // Good
new CreateTable('user_roles');      // Good
new CreateTable('order_items');     // Good

new CreateTable('User');            // Avoid - capitalization issues
new CreateTable('userRole');        // Avoid - camelCase
new CreateTable('user');            // Avoid - singular (debatable)
```

#### Column Names

```php
// Use singular, lowercase, snake_case
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('first_name', 100));
$table->addColumn(new Column\Integer('user_id')); // Foreign key

// Avoid
// 'firstName' - camelCase
// 'FirstName' - PascalCase
// 'FIRST_NAME' - all caps
```

#### Constraint Names

```php
// Primary keys: pk_{table}
new Constraint\PrimaryKey('id', 'pk_users');

// Foreign keys: fk_{table}_{referenced_table} OR fk_{table}_{column}
new Constraint\ForeignKey('fk_order_customer', 'customer_id', 'customers', 'id');
new Constraint\ForeignKey('fk_order_user', 'user_id', 'users', 'id');

// Unique constraints: unique_{table}_{column} OR unique_{descriptive_name}
new Constraint\UniqueKey('email', 'unique_user_email');
new Constraint\UniqueKey(['tenant_id', 'username'], 'unique_tenant_username');

// Check constraints: check_{descriptive_name}
new Constraint\Check('age >= 18', 'check_adult_age');
new Constraint\Check('price > 0', 'check_positive_price');
```

#### Index Names

```php
// idx_{table}_{column(s)} OR idx_{purpose}
new Index('email', 'idx_user_email');
new Index(['last_name', 'first_name'], 'idx_user_name');
new Index(['created_at', 'status'], 'idx_recent_active');
```

### Schema Migration Patterns

#### Pattern 1: Versioned Migrations

```php
class Migration_001_CreateUsersTable {
    public function up($adapter) {
        $sql = new Sql($adapter);
        $table = new CreateTable('users');

        $id = new Column\Integer('id');
        $id->setOption('AUTO_INCREMENT', true);
        $id->addConstraint(new Constraint\PrimaryKey());
        $table->addColumn($id);

        $table->addColumn(new Column\Varchar('email', 255));
        $table->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));

        $adapter->query($sql->buildSqlString($table), $adapter::QUERY_MODE_EXECUTE);
    }

    public function down($adapter) {
        $sql = new Sql($adapter);
        $drop = new DropTable('users');
        $adapter->query($sql->buildSqlString($drop), $adapter::QUERY_MODE_EXECUTE);
    }
}
```

#### Pattern 2: Safe Migrations

```php
// Check if table exists before creating
function safeCreateTable($adapter, $tableName, $ddlObject) {
    $sql = new Sql($adapter);

    // Check existence (platform-specific)
    $platformName = $adapter->getPlatform()->getName();

    $exists = false;
    if ($platformName === 'MySQL') {
        $result = $adapter->query(
            "SHOW TABLES LIKE '$tableName'",
            $adapter::QUERY_MODE_EXECUTE
        );
        $exists = $result->count() > 0;
    }

    if (!$exists) {
        $adapter->query(
            $sql->buildSqlString($ddlObject),
            $adapter::QUERY_MODE_EXECUTE
        );
    }
}
```

#### Pattern 3: Idempotent Migrations

```php
// Use IF NOT EXISTS (platform-specific)
// Note: PhpDb DDL doesn't support IF NOT EXISTS directly
// You'll need to handle this at the SQL level or check existence first

function createTableIfNotExists($adapter, $tableName, CreateTable $table) {
    $sql = new Sql($adapter);
    $platformName = $adapter->getPlatform()->getName();

    if ($platformName === 'MySQL') {
        // Manually construct IF NOT EXISTS
        $sqlString = $sql->buildSqlString($table);
        $sqlString = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $sqlString);
        $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
    } else {
        // Fallback: check and create
        safeCreateTable($adapter, $tableName, $table);
    }
}
```

### Performance Considerations

#### 1. Batch Multiple DDL Operations

```php
// Bad: Multiple ALTER TABLE statements
$alter1 = new AlterTable('users');
$alter1->addColumn(new Column\Varchar('phone', 20));
$adapter->query($sql->buildSqlString($alter1), $adapter::QUERY_MODE_EXECUTE);

$alter2 = new AlterTable('users');
$alter2->addColumn(new Column\Varchar('city', 100));
$adapter->query($sql->buildSqlString($alter2), $adapter::QUERY_MODE_EXECUTE);

// Good: Single ALTER TABLE with multiple operations
$alter = new AlterTable('users');
$alter->addColumn(new Column\Varchar('phone', 20));
$alter->addColumn(new Column\Varchar('city', 100));
$adapter->query($sql->buildSqlString($alter), $adapter::QUERY_MODE_EXECUTE);
```

#### 2. Add Indexes After Bulk Insert

```php
// For large initial data loads:

// 1. Create table without indexes
$table = new CreateTable('products');
$table->addColumn(new Column\Integer('id'));
$table->addColumn(new Column\Varchar('name', 255));
// ... more columns ...
$adapter->query($sql->buildSqlString($table), $adapter::QUERY_MODE_EXECUTE);

// 2. Load data
// ... insert thousands of rows ...

// 3. Add indexes after data is loaded
$alter = new AlterTable('products');
$alter->addConstraint(new Index('name', 'idx_name'));
$alter->addConstraint(new Index(['category_id', 'price'], 'idx_category_price'));
$adapter->query($sql->buildSqlString($alter), $adapter::QUERY_MODE_EXECUTE);
```

#### 3. Foreign Key Impact

Foreign keys add overhead to INSERT/UPDATE/DELETE operations:

### Disabling Foreign Key Checks for Bulk Operations

```php
// If you need to bulk load data, consider:
// 1. Disable foreign key checks (platform-specific)
// 2. Load data
// 3. Re-enable foreign key checks

// MySQL example (outside DDL abstraction):
$adapter->query('SET FOREIGN_KEY_CHECKS = 0', $adapter::QUERY_MODE_EXECUTE);
// ... bulk operations ...
$adapter->query('SET FOREIGN_KEY_CHECKS = 1', $adapter::QUERY_MODE_EXECUTE);
```

### Testing DDL Changes

#### 1. Test on Development Copy

```php
// Always test DDL on a copy of production data
$devAdapter = new Adapter($devConfig);
$prodAdapter = new Adapter($prodConfig);

// Test migration on dev first
try {
    executeMigration($devAdapter, $ddlObjects);
    echo "Dev migration successful\n";

    // If successful, run on production
    executeMigration($prodAdapter, $ddlObjects);
} catch (\Exception $e) {
    echo "Migration failed on dev: " . $e->getMessage() . "\n";
    // Don't touch production
}
```

#### 2. Generate and Review SQL

```php
// Generate DDL SQL and review before executing
$sql = new Sql($adapter);

foreach ($ddlObjects as $ddl) {
    $sqlString = $sql->buildSqlString($ddl);
    echo $sqlString . ";\n\n";
}

// Review output, then execute if satisfied
```

#### 3. Backup Before DDL

```php
function executeSafeDdl($adapter, $ddl) {
    // 1. Backup (implementation depends on platform)
    backupDatabase($adapter);

    // 2. Execute DDL
    try {
        $sql = new Sql($adapter);
        $adapter->query(
            $sql->buildSqlString($ddl),
            $adapter::QUERY_MODE_EXECUTE
        );
        return true;
    } catch (\Exception $e) {
        // 3. Restore on failure
        restoreDatabase($adapter);
        throw $e;
    }
}
```