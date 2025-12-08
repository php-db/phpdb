# Column Types Reference

All column types are in the `PhpDb\Sql\Ddl\Column` namespace and implement `ColumnInterface`.

## Numeric Types

### Integer

Standard integer column.

```php title="Creating Integer Columns"
use PhpDb\Sql\Ddl\Column\Integer;

$column = new Integer('user_id');
$column = new Integer('count', false, 0); // NOT NULL with default 0

// With display length (platform-specific)
$column = new Integer('user_id');
$column->setOption('length', 11);
```

**Constructor:**

```php
__construct(
    $name,
    $nullable = false,
    $default = null,
    array $options = []
)
```

**Methods:**

- `setNullable(bool $nullable): self`
- `isNullable(): bool`
- `setDefault(string|int|null $default): self`
- `getDefault(): string|int|null`
- `setOption(string $name, mixed $value): self`
- `setOptions(array $options): self`

### BigInteger

For larger integer values (typically 64-bit).

```php title="Creating BigInteger Columns"
use PhpDb\Sql\Ddl\Column\BigInteger;

$column = new BigInteger('large_number');
$column = new BigInteger('id', false, null, ['length' => 20]);
```

**Constructor:**

```php
__construct(
    $name,
    $nullable = false,
    $default = null,
    array $options = []
)
```

### Decimal

Fixed-point decimal numbers with precision and scale.

```php title="Creating Decimal Columns with Precision and Scale"
use PhpDb\Sql\Ddl\Column\Decimal;

$column = new Decimal('price', 10, 2); // DECIMAL(10,2)
$column = new Decimal('tax_rate', 5, 4); // DECIMAL(5,4)

// Can also be set after construction
$column = new Decimal('amount', 10);
$column->setDigits(12); // Change precision
$column->setDecimal(3); // Change scale
```

**Constructor:** `__construct($name, $precision, $scale = null)`

**Methods:**

- `setDigits(int $digits): self` - Set precision
- `getDigits(): int` - Get precision
- `setDecimal(int $decimal): self` - Set scale
- `getDecimal(): int` - Get scale

### Floating

Floating-point numbers.

```php title="Creating Floating Point Columns"
use PhpDb\Sql\Ddl\Column\Floating;

$column = new Floating('measurement', 10, 2);

// Adjustable after construction
$column->setDigits(12);
$column->setDecimal(4);
```

**Constructor:** `__construct($name, $digits, $decimal)`

> The class is named `Floating` rather than `Float` because `float` is a reserved
> keyword in PHP.

## String Types

### Varchar

Variable-length character string.

```php title="Creating Varchar Columns"
use PhpDb\Sql\Ddl\Column\Varchar;

$column = new Varchar('name', 255);
$column = new Varchar('email', 320); // Max email length

// Can be nullable
$column = new Varchar('middle_name', 100);
$column->setNullable(true);
```

**Constructor:** `__construct($name, $length)`

**Methods:**

- `setLength(int $length): self`
- `getLength(): int`

### Char

Fixed-length character string.

```php title="Creating Fixed-Length Char Columns"
use PhpDb\Sql\Ddl\Column\Char;

$column = new Char('country_code', 2); // ISO country codes
$column = new Char('status', 1); // Single character status
```

**Constructor:** `__construct($name, $length)`

### Text

Variable-length text for large strings.

```php title="Creating Text Columns"
use PhpDb\Sql\Ddl\Column\Text;

$column = new Text('description');
$column = new Text('content', 65535); // With length limit

// Can be nullable and have defaults
$column = new Text('notes', null, true, 'No notes');
```

**Constructor:**

```php
__construct(
    $name,
    $length = null,
    $nullable = false,
    $default = null,
    array $options = []
)
```

## Binary Types

### Binary

Fixed-length binary data.

```php title="Creating Binary Columns"
use PhpDb\Sql\Ddl\Column\Binary;

$column = new Binary('hash', 32); // 32-byte hash
```

**Constructor:**

```php
__construct(
    $name,
    $length,
    $nullable = false,
    $default = null,
    array $options = []
)
```

### Varbinary

Variable-length binary data.

```php title="Creating Varbinary Columns"
use PhpDb\Sql\Ddl\Column\Varbinary;

$column = new Varbinary('file_data', 65535);
```

**Constructor:** `__construct($name, $length)`

### Blob

Binary large object for very large binary data.

```php title="Creating Blob Columns"
use PhpDb\Sql\Ddl\Column\Blob;

$column = new Blob('image');
$column = new Blob('document', 16777215); // MEDIUMBLOB size
```

**Constructor:**

```php
__construct(
    $name,
    $length = null,
    $nullable = false,
    $default = null,
    array $options = []
)
```

## Date and Time Types

### Date

Date without time.

```php title="Creating Date Columns"
use PhpDb\Sql\Ddl\Column\Date;

$column = new Date('birth_date');
$column = new Date('hire_date');
```

**Constructor:** `__construct($name)`

### Time

Time without date.

```php title="Creating Time Columns"
use PhpDb\Sql\Ddl\Column\Time;

$column = new Time('start_time');
$column = new Time('duration');
```

**Constructor:** `__construct($name)`

### Datetime

Date and time combined.

```php title="Creating Datetime Columns"
use PhpDb\Sql\Ddl\Column\Datetime;

$column = new Datetime('last_login');
$column = new Datetime('event_time');
```

**Constructor:** `__construct($name)`

### Timestamp

Timestamp with special capabilities.

```php title="Creating Timestamp Columns with Auto-Update"
use PhpDb\Sql\Ddl\Column\Timestamp;

// Basic timestamp
$column = new Timestamp('created_at');
$column->setDefault('CURRENT_TIMESTAMP');

// With automatic update on row modification
$column = new Timestamp('updated_at');
$column->setDefault('CURRENT_TIMESTAMP');
$column->setOption('on_update', true);
// Generates: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

**Constructor:** `__construct($name)`

**Special Options:**

- `on_update` - When `true`, adds `ON UPDATE CURRENT_TIMESTAMP`

## Boolean Type

### Boolean

Boolean/bit column.

> **Note:** Boolean columns are always NOT NULL and cannot be made nullable.

```php title="Creating Boolean Columns"
use PhpDb\Sql\Ddl\Column\Boolean;

$column = new Boolean('is_active');
$column = new Boolean('is_verified');

// Attempting to make nullable has no effect
$column->setNullable(true); // Does nothing - stays NOT NULL
```

**Constructor:** `__construct($name)`

**Important:** The `setNullable()` method is overridden to always enforce NOT NULL.

## Generic Column Type

### Column

Generic column type (defaults to INTEGER). Use specific types when possible.

```php title="Creating Generic Columns"
use PhpDb\Sql\Ddl\Column\Column;

$column = new Column('custom_field');
```

**Constructor:** `__construct($name = null)`

## Common Column Methods

All column types share these methods:

```php title="Working with Nullable, Defaults, Options, and Constraints"
// Nullable setting
$column->setNullable(true);  // Allow NULL values
$column->setNullable(false); // NOT NULL (default for most types)
$isNullable = $column->isNullable();

// Default values
$column->setDefault('default_value');
$column->setDefault(0);
$column->setDefault(null);
$default = $column->getDefault();

// Options (platform-specific features)
$column->setOption('AUTO_INCREMENT', true);
$column->setOption('comment', 'User identifier');
$column->setOption('length', 11);
$column->setOptions(['AUTO_INCREMENT' => true, 'comment' => 'ID']);

// Constraints (column-level)
$column->addConstraint(new Constraint\PrimaryKey());

// Name
$name = $column->getName();
```

## Column Options Reference

Column options provide a flexible way to specify platform-specific features and metadata.

### Setting Options

```php title="Setting Single and Multiple Column Options"
// Set single option
$column->setOption('option_name', 'option_value');

// Set multiple options
$column->setOptions([
    'option1' => 'value1',
    'option2' => 'value2',
]);

// Get all options
$options = $column->getOptions();
```

### Documented Options

| Option           | Type   | Platforms         | Description                 |
| ---------------- | ------ | ----------------- | --------------------------- |
| `AUTO_INCREMENT` | bool   | MySQL, MariaDB    | Auto-increment integer      |
| `identity`       | bool   | PostgreSQL, MSSQL | Identity/Serial column      |
| `comment`        | string | MySQL, PostgreSQL | Column comment              |
| `on_update`      | bool   | MySQL (Timestamp) | ON UPDATE CURRENT_TIMESTAMP |
| `length`         | int    | MySQL (Integer)   | Display width               |

### MySQL/MariaDB Specific Options

```php title="Using MySQL-Specific Column Modifiers"
// UNSIGNED modifier
$column = new Column\Integer('count');
$column->setOption('unsigned', true);
// Generates: `count` INT UNSIGNED NOT NULL

// ZEROFILL modifier
$column = new Column\Integer('code');
$column->setOption('zerofill', true);
// Generates: `code` INT ZEROFILL NOT NULL

// Character set
$column = new Column\Varchar('name', 255);
$column->setOption('charset', 'utf8mb4');
// Generates: `name` VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL

// Collation
$column = new Column\Varchar('name', 255);
$column->setOption('collation', 'utf8mb4_unicode_ci');
// Generates: `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL
```

### PostgreSQL Specific Options

```php title="Creating Serial/Identity Columns in PostgreSQL"
// SERIAL type (via identity option)
$id = new Column\Integer('id');
$id->setOption('identity', true);
// Generates: "id" SERIAL NOT NULL
```

### SQL Server Specific Options

```php title="Creating Identity Columns in SQL Server"
// IDENTITY column
$id = new Column\Integer('id');
$id->setOption('identity', true);
// Generates: [id] INT IDENTITY NOT NULL
```

### Common Option Patterns

#### Auto-Incrementing Primary Key

```php title="Creating Auto-Incrementing Primary Keys"
// MySQL
$id = new Column\Integer('id');
$id->setOption('AUTO_INCREMENT', true);
$id->addConstraint(new Constraint\PrimaryKey());
$table->addColumn($id);

// PostgreSQL/SQL Server
$id = new Column\Integer('id');
$id->setOption('identity', true);
$id->addConstraint(new Constraint\PrimaryKey());
$table->addColumn($id);
```

#### Timestamp with Auto-Update

```php title="Creating Self-Updating Timestamp Columns"
$updated = new Column\Timestamp('updated_at');
$updated->setDefault('CURRENT_TIMESTAMP');
$updated->setOption('on_update', true);
$table->addColumn($updated);
// MySQL: updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### Documented Column with Comment

```php title="Adding Comments to Column Definitions"
$column = new Column\Varchar('email', 255);
$column->setOption('comment', 'User email address for authentication');
$table->addColumn($column);
```

### Option Compatibility Notes

**Important Considerations:**

1. **Not all options work on all platforms** - Test your DDL against your
   target database
2. **Some options are silently ignored** on unsupported platforms
3. **Platform rendering varies** - the same option may produce different SQL
   on different platforms
4. **Options are not validated** by DDL objects - invalid options may cause
   SQL errors during execution

## Column Type Selection Best Practices

### Numeric Type Selection

#### Choosing the Right Numeric Type

```php
// Use Integer for most numeric IDs and counters
$id = new Column\Integer('id');           // -2,147,483,648 to 2,147,483,647
$count = new Column\Integer('view_count');

// Use BigInteger for very large numbers
$bigId = new Column\BigInteger('user_id'); // -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807

// Use Decimal for money and precise calculations
$price = new Column\Decimal('price', 10, 2);     // DECIMAL(10,2) - $99,999,999.99
$tax = new Column\Decimal('tax_rate', 5, 4);     // DECIMAL(5,4) - 0.9999 (99.99%)

// Use Floating for scientific/approximate calculations (avoid for money!)
$latitude = new Column\Floating('lat', 10, 6);   // GPS coordinates
$measurement = new Column\Floating('temp', 5, 2); // Temperature readings
```

### String Type Selection

#### Choosing the Right String Type

```php
// Use Varchar for bounded strings with known max length
$email = new Column\Varchar('email', 320);       // Max email length (RFC 5321)
$username = new Column\Varchar('username', 50);
$countryCode = new Column\Varchar('country', 2); // ISO 3166-1 alpha-2

// Use Char for fixed-length strings
$statusCode = new Column\Char('status', 1);      // Single character: 'A', 'P', 'C'
$currencyCode = new Column\Char('currency', 3);  // ISO 4217: 'USD', 'EUR', 'GBP'

// Use Text for unbounded or very large strings
$description = new Column\Text('description');    // Product descriptions
$content = new Column\Text('article_content');    // Article content
$notes = new Column\Text('notes');                // User notes
```

**Rule of Thumb:**

- String <= 255 chars with known max → Varchar
- Fixed length → Char
- No length limit or very large → Text

### Date/Time Types

```php title="Choosing the Right Date and Time Type"
// Use Date for dates without time
$birthDate = new Column\Date('birth_date');
$eventDate = new Column\Date('event_date');

// Use Time for times without date
$openTime = new Column\Time('opening_time');
$duration = new Column\Time('duration');

// Use Datetime for specific moments in time (platform-agnostic)
$appointmentTime = new Column\Datetime('appointment_at');
$publishedAt = new Column\Datetime('published_at');

// Use Timestamp for automatic tracking (created/updated)
$created = new Column\Timestamp('created_at');
$created->setDefault('CURRENT_TIMESTAMP');

$updated = new Column\Timestamp('updated_at');
$updated->setDefault('CURRENT_TIMESTAMP');
$updated->setOption('on_update', true);
```
