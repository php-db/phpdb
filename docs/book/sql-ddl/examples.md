# DDL Examples and Patterns

## Example 1: E-Commerce Product Table

```php title="Creating a Complete Product Table with Constraints and Indexes"
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Index\Index;

$table = new CreateTable('products');

// Primary key with auto-increment
$id = new Column\Integer('id');
$id->setOption('AUTO_INCREMENT', true);
$id->addConstraint(new Constraint\PrimaryKey());
$table->addColumn($id);

// Basic product info
$table->addColumn(new Column\Varchar('sku', 50));
$table->addColumn(new Column\Varchar('name', 255));
$table->addColumn(new Column\Text('description'));

// Pricing
$table->addColumn(new Column\Decimal('price', 10, 2));
$table->addColumn(new Column\Decimal('cost', 10, 2));

// Inventory
$table->addColumn(new Column\Integer('stock_quantity'));

// Foreign key to category
$table->addColumn(new Column\Integer('category_id'));
$table->addConstraint(new Constraint\ForeignKey(
    'fk_product_category',
    'category_id',
    'categories',
    'id',
    'RESTRICT',  // Don't allow category deletion if products exist
    'CASCADE'    // Update category_id if category.id changes
));

// Status and flags
$table->addColumn(new Column\Boolean('is_active'));
$table->addColumn(new Column\Boolean('is_featured'));

// Timestamps
$created = new Column\Timestamp('created_at');
$created->setDefault('CURRENT_TIMESTAMP');
$table->addColumn($created);

$updated = new Column\Timestamp('updated_at');
$updated->setDefault('CURRENT_TIMESTAMP');
$updated->setOption('on_update', true);
$table->addColumn($updated);

// Constraints
$table->addConstraint(new Constraint\UniqueKey('sku', 'unique_product_sku'));
$table->addConstraint(new Constraint\Check('price >= cost', 'check_profitable_price'));
$table->addConstraint(new Constraint\Check('stock_quantity >= 0', 'check_non_negative_stock'));

// Indexes for performance
$table->addConstraint(new Index('category_id', 'idx_category'));
$table->addConstraint(new Index('sku', 'idx_sku'));
$table->addConstraint(new Index(['is_active', 'is_featured'], 'idx_active_featured'));
$table->addConstraint(new Index('name', 'idx_name_search', [100]));

// Execute
$sql = new Sql($adapter);
$adapter->query($sql->buildSqlString($table), $adapter::QUERY_MODE_EXECUTE);
```

## Example 2: User Authentication System

```php title="Building a Multi-Table User Authentication Schema with Roles"
// Users table
$users = new CreateTable('users');

$id = new Column\Integer('id');
$id->setOption('AUTO_INCREMENT', true);
$id->addConstraint(new Constraint\PrimaryKey());
$users->addColumn($id);

$users->addColumn(new Column\Varchar('username', 50));
$users->addColumn(new Column\Varchar('email', 255));
$users->addColumn(new Column\Varchar('password_hash', 255));

$lastLogin = new Column\Timestamp('last_login');
$lastLogin->setNullable(true);
$users->addColumn($lastLogin);

$users->addColumn(new Column\Boolean('is_active'));
$users->addColumn(new Column\Boolean('is_verified'));

$users->addConstraint(new Constraint\UniqueKey('username', 'unique_username'));
$users->addConstraint(new Constraint\UniqueKey('email', 'unique_email'));
$users->addConstraint(new Index(['username', 'email'], 'idx_user_search'));

// Execute
$adapter->query($sql->buildSqlString($users), $adapter::QUERY_MODE_EXECUTE);

// Roles table
$roles = new CreateTable('roles');

$roleId = new Column\Integer('id');
$roleId->setOption('AUTO_INCREMENT', true);
$roleId->addConstraint(new Constraint\PrimaryKey());
$roles->addColumn($roleId);

$roles->addColumn(new Column\Varchar('name', 50));
$roles->addColumn(new Column\Text('description'));
$roles->addConstraint(new Constraint\UniqueKey('name', 'unique_role_name'));

$adapter->query($sql->buildSqlString($roles), $adapter::QUERY_MODE_EXECUTE);

// User-Role junction table
$userRoles = new CreateTable('user_roles');

$userRoles->addColumn(new Column\Integer('user_id'));
$userRoles->addColumn(new Column\Integer('role_id'));

// Composite primary key
$userRoles->addConstraint(new Constraint\PrimaryKey(['user_id', 'role_id']));

// Foreign keys
$userRoles->addConstraint(new Constraint\ForeignKey(
    'fk_user_role_user',
    'user_id',
    'users',
    'id',
    'CASCADE',  // Delete role assignments when user is deleted
    'CASCADE'
));

$userRoles->addConstraint(new Constraint\ForeignKey(
    'fk_user_role_role',
    'role_id',
    'roles',
    'id',
    'CASCADE',  // Delete role assignments when role is deleted
    'CASCADE'
));

// Indexes
$userRoles->addConstraint(new Index('user_id', 'idx_user'));
$userRoles->addConstraint(new Index('role_id', 'idx_role'));

$adapter->query($sql->buildSqlString($userRoles), $adapter::QUERY_MODE_EXECUTE);
```

## Example 3: Multi-Tenant Schema

```php title="Implementing Cross-Schema Tables with Foreign Key References"
use PhpDb\Sql\TableIdentifier;

// Tenants table (in public schema)
$tenants = new CreateTable(new TableIdentifier('tenants', 'public'));

$tenantId = new Column\Integer('id');
$tenantId->setOption('AUTO_INCREMENT', true);
$tenantId->addConstraint(new Constraint\PrimaryKey());
$tenants->addColumn($tenantId);

$tenants->addColumn(new Column\Varchar('name', 255));
$tenants->addColumn(new Column\Varchar('subdomain', 100));
$tenants->addColumn(new Column\Boolean('is_active'));

$tenants->addConstraint(new Constraint\UniqueKey('subdomain', 'unique_subdomain'));

$adapter->query($sql->buildSqlString($tenants), $adapter::QUERY_MODE_EXECUTE);

// Tenant-specific users table (in tenant schema)
$tenantUsers = new CreateTable(new TableIdentifier('users', 'tenant_schema'));

$userId = new Column\Integer('id');
$userId->setOption('AUTO_INCREMENT', true);
$userId->addConstraint(new Constraint\PrimaryKey());
$tenantUsers->addColumn($userId);

$tenantUsers->addColumn(new Column\Integer('tenant_id'));
$tenantUsers->addColumn(new Column\Varchar('username', 50));
$tenantUsers->addColumn(new Column\Varchar('email', 255));

// Composite unique constraint (username unique per tenant)
$tenantUsers->addConstraint(new Constraint\UniqueKey(
    ['tenant_id', 'username'],
    'unique_tenant_username'
));

// Foreign key to public.tenants
$tenantUsers->addConstraint(new Constraint\ForeignKey(
    'fk_user_tenant',
    'tenant_id',
    new TableIdentifier('tenants', 'public'),
    'id',
    'CASCADE',
    'CASCADE'
));

$adapter->query($sql->buildSqlString($tenantUsers), $adapter::QUERY_MODE_EXECUTE);
```

## Example 4: Database Migration Pattern

```php title="Creating Reversible Migration Classes with Up and Down Methods"
use PhpDb\Sql\Sql;
use PhpDb\Sql\Ddl;

class Migration_001_CreateUsersTable
{
    public function up($adapter)
    {
        $sql = new Sql($adapter);

        $table = new Ddl\CreateTable('users');

        $id = new Ddl\Column\Integer('id');
        $id->setOption('AUTO_INCREMENT', true);
        $id->addConstraint(new Ddl\Constraint\PrimaryKey());
        $table->addColumn($id);

        $table->addColumn(new Ddl\Column\Varchar('email', 255));
        $table->addColumn(new Ddl\Column\Varchar('password_hash', 255));
        $table->addColumn(new Ddl\Column\Boolean('is_active'));

        $table->addConstraint(new Ddl\Constraint\UniqueKey('email', 'unique_email'));

        $adapter->query(
            $sql->buildSqlString($table),
            $adapter::QUERY_MODE_EXECUTE
        );
    }

    public function down($adapter)
    {
        $sql = new Sql($adapter);
        $drop = new Ddl\DropTable('users');

        $adapter->query(
            $sql->buildSqlString($drop),
            $adapter::QUERY_MODE_EXECUTE
        );
    }
}

class Migration_002_AddUserProfiles
{
    public function up($adapter)
    {
        $sql = new Sql($adapter);

        $alter = new Ddl\AlterTable('users');

        $alter->addColumn(new Ddl\Column\Varchar('first_name', 100));
        $alter->addColumn(new Ddl\Column\Varchar('last_name', 100));

        $bio = new Ddl\Column\Text('bio');
        $bio->setNullable(true);
        $alter->addColumn($bio);

        $adapter->query(
            $sql->buildSqlString($alter),
            $adapter::QUERY_MODE_EXECUTE
        );
    }

    public function down($adapter)
    {
        $sql = new Sql($adapter);

        $alter = new Ddl\AlterTable('users');
        $alter->dropColumn('first_name');
        $alter->dropColumn('last_name');
        $alter->dropColumn('bio');

        $adapter->query(
            $sql->buildSqlString($alter),
            $adapter::QUERY_MODE_EXECUTE
        );
    }
}
```

## Example 5: Audit Log Table

```php title="Designing an Audit Trail Table for Tracking Data Changes"
$auditLog = new CreateTable('audit_log');

// Auto-increment ID
$id = new Column\BigInteger('id');
$id->setOption('AUTO_INCREMENT', true);
$id->addConstraint(new Constraint\PrimaryKey());
$auditLog->addColumn($id);

// What was changed
$auditLog->addColumn(new Column\Varchar('table_name', 100));
$auditLog->addColumn(new Column\Varchar('action', 20)); // INSERT, UPDATE, DELETE
$auditLog->addColumn(new Column\BigInteger('record_id'));

// Who changed it
$userId = new Column\Integer('user_id');
$userId->setNullable(true); // System actions might not have a user
$auditLog->addColumn($userId);

// When it changed
$timestamp = new Column\Timestamp('created_at');
$timestamp->setDefault('CURRENT_TIMESTAMP');
$auditLog->addColumn($timestamp);

// What changed (JSON or TEXT)
$auditLog->addColumn(new Column\Text('old_values'));
$auditLog->addColumn(new Column\Text('new_values'));

// Additional context
$ipAddress = new Column\Varchar('ip_address', 45); // IPv6 compatible
$ipAddress->setNullable(true);
$auditLog->addColumn($ipAddress);

// Constraints
$auditLog->addConstraint(new Constraint\Check(
    "action IN ('INSERT', 'UPDATE', 'DELETE')",
    'check_valid_action'
));

// Indexes for querying
$auditLog->addConstraint(new Index('table_name', 'idx_table'));
$auditLog->addConstraint(new Index('record_id', 'idx_record'));
$auditLog->addConstraint(new Index('user_id', 'idx_user'));
$auditLog->addConstraint(new Index('created_at', 'idx_created'));
$auditLog->addConstraint(new Index(['table_name', 'record_id'], 'idx_table_record'));

$adapter->query($sql->buildSqlString($auditLog), $adapter::QUERY_MODE_EXECUTE);
```

## Example 6: Session Storage Table

```php title="Building a Database-Backed Session Storage System"
$sessions = new CreateTable('sessions');

// Session ID as primary key (not auto-increment)
$sessionId = new Column\Varchar('id', 128);
$sessionId->addConstraint(new Constraint\PrimaryKey());
$sessions->addColumn($sessionId);

// User association (optional - anonymous sessions allowed)
$userId = new Column\Integer('user_id');
$userId->setNullable(true);
$sessions->addColumn($userId);

// Session data
$sessions->addColumn(new Column\Text('data'));

// Timestamps for expiration
$createdAt = new Column\Timestamp('created_at');
$createdAt->setDefault('CURRENT_TIMESTAMP');
$sessions->addColumn($createdAt);

$expiresAt = new Column\Timestamp('expires_at');
$sessions->addColumn($expiresAt);

$lastActivity = new Column\Timestamp('last_activity');
$lastActivity->setDefault('CURRENT_TIMESTAMP');
$lastActivity->setOption('on_update', true);
$sessions->addColumn($lastActivity);

// IP and user agent for security
$sessions->addColumn(new Column\Varchar('ip_address', 45));
$sessions->addColumn(new Column\Varchar('user_agent', 255));

// Foreign key to users (SET NULL on delete - preserve session data)
$sessions->addConstraint(new Constraint\ForeignKey(
    'fk_session_user',
    'user_id',
    'users',
    'id',
    'SET NULL',
    'CASCADE'
));

// Indexes
$sessions->addConstraint(new Index('user_id', 'idx_user'));
$sessions->addConstraint(new Index('expires_at', 'idx_expires'));
$sessions->addConstraint(new Index('last_activity', 'idx_activity'));

$adapter->query($sql->buildSqlString($sessions), $adapter::QUERY_MODE_EXECUTE);
```

## Example 7: File Storage Metadata Table

```php title="Implementing File Metadata Storage with UUID Primary Keys"
$files = new CreateTable('files');

// UUID as primary key
$id = new Column\Char('id', 36); // UUID format
$id->addConstraint(new Constraint\PrimaryKey());
$files->addColumn($id);

// File information
$files->addColumn(new Column\Varchar('original_name', 255));
$files->addColumn(new Column\Varchar('stored_name', 255));
$files->addColumn(new Column\Varchar('mime_type', 100));
$files->addColumn(new Column\BigInteger('file_size'));
$files->addColumn(new Column\Varchar('storage_path', 500));

// Hash for deduplication
$files->addColumn(new Column\Char('content_hash', 64)); // SHA-256

// Ownership
$files->addColumn(new Column\Integer('uploaded_by'));
$uploadedAt = new Column\Timestamp('uploaded_at');
$uploadedAt->setDefault('CURRENT_TIMESTAMP');
$files->addColumn($uploadedAt);

// Soft delete
$deletedAt = new Column\Timestamp('deleted_at');
$deletedAt->setNullable(true);
$files->addColumn($deletedAt);

// Constraints
$files->addConstraint(new Constraint\UniqueKey('stored_name', 'unique_stored_name'));
$files->addConstraint(new Constraint\ForeignKey(
    'fk_file_user',
    'uploaded_by',
    'users',
    'id',
    'RESTRICT', // Don't allow user deletion if they have files
    'CASCADE'
));

// Indexes
$files->addConstraint(new Index('content_hash', 'idx_hash'));
$files->addConstraint(new Index('uploaded_by', 'idx_uploader'));
$files->addConstraint(new Index('mime_type', 'idx_mime'));
$files->addConstraint(new Index(['deleted_at', 'uploaded_at'], 'idx_active_files'));

$adapter->query($sql->buildSqlString($files), $adapter::QUERY_MODE_EXECUTE);
```

## Troubleshooting Common Issues

### Issue: Table Already Exists

```php title="Safely Creating Tables with Existence Checks"
// Check before creating
function createTableIfNotExists($adapter, CreateTable $table) {
    $sql = new Sql($adapter);
    $tableName = $table->getRawState()['table'];

    try {
        $adapter->query(
            $sql->buildSqlString($table),
            $adapter::QUERY_MODE_EXECUTE
        );
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            // Table exists, that's fine
            return false;
        }
        throw $e;
    }
    return true;
}
```

### Issue: Foreign Key Constraint Fails

```php title="Ensuring Correct Table Creation Order for Foreign Keys"
// Ensure referenced table exists first
$sql = new Sql($adapter);

// 1. Create parent table first
$roles = new CreateTable('roles');
// ... add columns ...
$adapter->query($sql->buildSqlString($roles), $adapter::QUERY_MODE_EXECUTE);

// 2. Then create child table with foreign key
$userRoles = new CreateTable('user_roles');
// ... add columns and foreign key to roles ...
$adapter->query($sql->buildSqlString($userRoles), $adapter::QUERY_MODE_EXECUTE);
```

### Issue: Column Type Mismatch in Foreign Key

```php title="Matching Column Types Between Parent and Child Tables"
// Ensure both columns have the same type
$parentTable = new CreateTable('categories');
$parentId = new Column\Integer('id'); // INTEGER
$parentId->addConstraint(new Constraint\PrimaryKey());
$parentTable->addColumn($parentId);

$childTable = new CreateTable('products');
$childTable->addColumn(new Column\Integer('category_id')); // Must also be INTEGER
$childTable->addConstraint(new Constraint\ForeignKey(
    'fk_product_category',
    'category_id', // INTEGER
    'categories',
    'id'           // INTEGER - matches!
));
```

### Issue: Index Too Long

```php title="Using Prefix Indexes for Long Text Columns"
// Use prefix indexes for long text columns
$table->addConstraint(new Index(
    'long_description',
    'idx_description',
    [191] // MySQL InnoDB with utf8mb4 has 767 byte limit; 191 chars * 4 bytes = 764
));
```
