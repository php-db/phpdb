# Constraints and Indexes

Constraints enforce data integrity rules at the database level. All constraints are in the `PhpDb\Sql\Ddl\Constraint` namespace.

## Primary Key Constraints

A primary key uniquely identifies each row in a table.

### Single-Column Primary Key

```php
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;

// Simple - name is optional
$pk = new Constraint\PrimaryKey('id');

// With explicit name
$pk = new Constraint\PrimaryKey('id', 'pk_users');
```

### Composite Primary Key

Multiple columns together form the primary key:

```php
// Composite primary key
$pk = new Constraint\PrimaryKey(['user_id', 'role_id']);

// With explicit name
$pk = new Constraint\PrimaryKey(
    ['user_id', 'role_id'],
    'pk_user_roles'
);
```

### Column-Level Primary Key

Attach primary key directly to a column:

```php
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;

$id = new Integer('id');
$id->setOption('AUTO_INCREMENT', true);
$id->addConstraint(new PrimaryKey());

$table->addColumn($id);
```

**Generated SQL:**

```sql
"id" INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT
```

## Foreign Key Constraints

Foreign keys enforce referential integrity between tables.

### Basic Foreign Key

```php
use PhpDb\Sql\Ddl\Constraint\ForeignKey;

$fk = new ForeignKey(
    'fk_order_customer',  // Constraint name (required)
    'customer_id',         // Column in this table
    'customers',           // Referenced table
    'id'                   // Referenced column
);

$table->addConstraint($fk);
```

**Generated SQL:**

```sql
CONSTRAINT "fk_order_customer" FOREIGN KEY ("customer_id")
    REFERENCES "customers" ("id")
```

### Foreign Key with Referential Actions

Control what happens when referenced rows are deleted or updated:

```php
$fk = new ForeignKey(
    'fk_order_customer',
    'customer_id',
    'customers',
    'id',
    'CASCADE',    // ON DELETE CASCADE - delete orders when customer is deleted
    'RESTRICT'    // ON UPDATE RESTRICT - prevent customer ID changes if orders exist
);
```

**Available Actions:**

- `CASCADE` - Propagate the change to dependent rows
- `SET NULL` - Set foreign key column to NULL
- `RESTRICT` - Prevent the change if dependent rows exist
- `NO ACTION` - Similar to RESTRICT (default)

**Common Patterns:**

### Common Foreign Key Action Patterns

```php
// Delete child records when parent is deleted
$fk = new ForeignKey('fk_name', 'parent_id', 'parents', 'id', 'CASCADE');

// Set to NULL when parent is deleted (requires nullable column)
$fk = new ForeignKey('fk_name', 'parent_id', 'parents', 'id', 'SET NULL');

// Prevent deletion if child records exist
$fk = new ForeignKey('fk_name', 'parent_id', 'parents', 'id', 'RESTRICT');
```

### Composite Foreign Key

Multiple columns reference multiple columns in another table:

```php
$fk = new ForeignKey(
    'fk_user_tenant',
    ['user_id', 'tenant_id'],      // Local columns (array)
    'user_tenants',                 // Referenced table
    ['user_id', 'tenant_id'],      // Referenced columns (array)
    'CASCADE',
    'CASCADE'
);
```

**Generated SQL:**

```sql
CONSTRAINT "fk_user_tenant" FOREIGN KEY ("user_id", "tenant_id")
    REFERENCES "user_tenants" ("user_id", "tenant_id")
    ON DELETE CASCADE ON UPDATE CASCADE
```

## Unique Constraints

Unique constraints ensure column values are unique across all rows.

### Single-Column Unique Constraint

```php
use PhpDb\Sql\Ddl\Constraint\UniqueKey;

// Simple - name is optional
$unique = new UniqueKey('email');

// With explicit name
$unique = new UniqueKey('email', 'unique_user_email');

$table->addConstraint($unique);
```

**Generated SQL:**

```sql
CONSTRAINT "unique_user_email" UNIQUE ("email")
```

### Composite Unique Constraint

Multiple columns together must be unique:

```php
// Username + tenant must be unique together
$unique = new UniqueKey(
    ['username', 'tenant_id'],
    'unique_username_per_tenant'
);
```

**Generated SQL:**

```sql
CONSTRAINT "unique_username_per_tenant" UNIQUE ("username", "tenant_id")
```

## Check Constraints

Check constraints enforce custom validation rules.

### Simple Check Constraints

```php
use PhpDb\Sql\Ddl\Constraint\Check;

// Age must be 18 or older
$check = new Check('age >= 18', 'check_adult_age');
$table->addConstraint($check);

// Price must be positive
$check = new Check('price > 0', 'check_positive_price');
$table->addConstraint($check);

// Email must contain @
$check = new Check('email LIKE "%@%"', 'check_email_format');
$table->addConstraint($check);
```

### Complex Check Constraints

```php
// Discount percentage must be between 0 and 100
$check = new Check(
    'discount_percent >= 0 AND discount_percent <= 100',
    'check_valid_discount'
);

// End date must be after start date
$check = new Check(
    'end_date > start_date',
    'check_date_range'
);

// Status must be one of specific values
$check = new Check(
    "status IN ('pending', 'active', 'completed', 'cancelled')",
    'check_valid_status'
);
```

### Using Expressions in Check Constraints

Check constraints can accept either string expressions or `Expression` objects.

#### String Expressions (Simple)

For simple constraints, use strings:

```php
use PhpDb\Sql\Ddl\Constraint\Check;

// Simple string expression
$check = new Check('age >= 18', 'check_adult');
$check = new Check('price > 0', 'check_positive_price');
$check = new Check("status IN ('active', 'pending', 'completed')", 'check_valid_status');
```

#### Expression Objects (Advanced)

For complex or parameterized constraints, use `Expression` objects:

```php
use PhpDb\Sql\Expression;
use PhpDb\Sql\Ddl\Constraint\Check;

// Expression with parameters
$expr = new Expression(
    'age >= ? AND age <= ?',
    [18, 120]
);
$check = new Check($expr, 'check_valid_age_range');

// Complex expression
$expr = new Expression(
    'discount_percent BETWEEN ? AND ?',
    [0, 100]
);
$check = new Check($expr, 'check_discount_range');
```

## Indexes

Indexes improve query performance by creating fast lookup structures. The `Index` class is in the `PhpDb\Sql\Ddl\Index` namespace.

### Basic Index Creation

```php
use PhpDb\Sql\Ddl\Index\Index;

// Single column index
$index = new Index('username', 'idx_username');
$table->addConstraint($index);

// With explicit name
$index = new Index('email', 'idx_user_email');
$table->addConstraint($index);
```

**Generated SQL:**

```sql
INDEX "idx_username" ("username")
```

### Composite Indexes

Index multiple columns together:

```php
// Index on category and price (useful for filtered sorts)
$index = new Index(['category', 'price'], 'idx_category_price');
$table->addConstraint($index);

// Index on last_name, first_name (useful for name searches)
$index = new Index(['last_name', 'first_name'], 'idx_name_search');
$table->addConstraint($index);
```

**Generated SQL:**

```sql
INDEX "idx_category_price" ("category", "price")
```

### Index with Column Length Specifications

For large text columns, you can index only a prefix:

```php
// Index first 50 characters of title
$index = new Index('title', 'idx_title', [50]);
$table->addConstraint($index);

// Composite index with different lengths per column
$index = new Index(
    ['title', 'description'],
    'idx_search',
    [50, 100]  // Index 50 chars of title, 100 of description
);
$table->addConstraint($index);
```

**Generated SQL (platform-specific):**

```sql
INDEX "idx_search" ("title"(50), "description"(100))
```

**Why use length specifications?**

- Reduces index size for large text columns
- Improves index creation and maintenance performance
- Particularly useful for VARCHAR/TEXT columns that store long content

### Adding Indexes to Existing Tables

Use `AlterTable` to add indexes:

```php
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Index\Index;

$alter = new AlterTable('products');

// Add single-column index
$alter->addConstraint(new Index('sku', 'idx_product_sku'));

// Add composite index
$alter->addConstraint(new Index(
    ['category_id', 'created_at'],
    'idx_category_date'
));

// Add index with length limit
$alter->addConstraint(new Index('description', 'idx_description', [200]));
```

### Dropping Indexes

Remove existing indexes from a table:

```php
$alter = new AlterTable('products');
$alter->dropIndex('idx_old_search');
$alter->dropIndex('idx_deprecated_field');
```

## Naming Conventions

While some constraints allow optional names, it's a best practice to always provide explicit names:

### Best Practice: Using Explicit Constraint Names

```php
// Good - explicit names for all constraints
$table->addConstraint(new Constraint\PrimaryKey('id', 'pk_users'));
$table->addConstraint(new Constraint\UniqueKey('email', 'unique_user_email'));
$table->addConstraint(new Constraint\ForeignKey(
    'fk_user_role',
    'role_id',
    'roles',
    'id'
));

// This makes it easier to drop or modify constraints later
$alter->dropConstraint('unique_user_email');
$alter->dropConstraint('fk_user_role');
```

**Recommended Naming Patterns:**

- Primary keys: `pk_<table_name>`
- Foreign keys: `fk_<table>_<referenced_table>` or `fk_<table>_<column>`
- Unique constraints: `unique_<table>_<column>` or `unique_<descriptive_name>`
- Check constraints: `check_<descriptive_name>`
- Indexes: `idx_<table>_<column(s)>` or `idx_<purpose>`

## Index Strategy Best Practices

### When to Add Indexes

**DO index:**

- Primary keys (automatic in most platforms)
- Foreign key columns
- Columns frequently used in WHERE clauses
- Columns used in JOIN conditions
- Columns used in ORDER BY clauses
- Columns used in GROUP BY clauses

**DON'T index:**

- Very small tables (< 1000 rows)
- Columns with low cardinality (few unique values) like boolean
- Columns rarely used in queries
- Columns that change frequently in write-heavy tables

### Index Best Practices

### Implementing Indexing Best Practices

```php
// 1. Index foreign keys
$table->addColumn(new Column\Integer('user_id'));
$table->addConstraint(new Constraint\ForeignKey(
    'fk_order_user',
    'user_id',
    'users',
    'id'
));
$table->addConstraint(new Index('user_id', 'idx_user'));

// 2. Composite indexes for common query patterns
// If you often query: WHERE category_id = ? ORDER BY created_at DESC
$table->addConstraint(new Index(['category_id', 'created_at'], 'idx_category_date'));

// 3. Covering indexes (columns used together in WHERE/ORDER)
// Query: WHERE status = 'active' AND priority = 'high' ORDER BY created_at
$table->addConstraint(new Index(['status', 'priority', 'created_at'], 'idx_active_priority'));

// 4. Prefix indexes for large text columns
$table->addConstraint(new Index('title', 'idx_title', [100])); // Index first 100 chars
```

### Index Order Matters

### Optimal vs Suboptimal Index Column Order

```php
// For query: WHERE category_id = ? ORDER BY created_at DESC
new Index(['category_id', 'created_at'], 'idx_category_date'); // Good

// Less effective for the same query:
new Index(['created_at', 'category_id'], 'idx_date_category'); // Not optimal
```

**Rule:** Most selective (filters most rows) columns should come first.

## Complete Constraint Example

```php
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Index\Index;

$table = new CreateTable('articles');

// Columns
$table->addColumn((new Column\Integer('id'))->addConstraint(new Constraint\PrimaryKey()));
$table->addColumn(new Column\Varchar('title', 255));
$table->addColumn(new Column\Text('content'));
$table->addColumn(new Column\Integer('category_id'));
$table->addColumn(new Column\Integer('author_id'));
$table->addColumn(new Column\Timestamp('published_at'));
$table->addColumn(new Column\Boolean('is_published'));

// Indexes for performance
$table->addConstraint(new Index('category_id', 'idx_category'));
$table->addConstraint(new Index('author_id', 'idx_author'));
$table->addConstraint(new Index('published_at', 'idx_published_date'));

// Composite indexes
$table->addConstraint(new Index(
    ['is_published', 'published_at'],
    'idx_published_articles'
));

$table->addConstraint(new Index(
    ['category_id', 'published_at'],
    'idx_category_date'
));

// Text search index with length limit
$table->addConstraint(new Index('title', 'idx_title_search', [100]));
```
