# Advanced SQL Features

## Expression and Literal

### Distinguishing between Expression and Literal

Use `Literal` for static SQL fragments without parameters:

### Creating static SQL literals

```php
use PhpDb\Sql\Literal;

$literal = new Literal('NOW()');
$literal = new Literal('CURRENT_TIMESTAMP');
$literal = new Literal('COUNT(*)');
```

Use `Expression` when parameters are needed:

### Creating expressions with parameters

```php
use PhpDb\Sql\Expression;

$expression = new Expression('DATE_ADD(NOW(), INTERVAL ? DAY)', [7]);
$expression = new Expression('CONCAT(?, ?)', ['Hello', 'World']);
```

### Mixed parameter types in expressions

```php
use PhpDb\Sql\Argument;

$expression = new Expression(
    'CASE WHEN ? > ? THEN ? ELSE ? END',
    [
        Argument::identifier('age'),
        Argument::value(18),
        Argument::literal('ADULT'),
        Argument::literal('MINOR'),
    ]
);
```

Produces:

### SQL output for mixed parameter types

```sql
CASE WHEN age > 18 THEN ADULT ELSE MINOR END
```

### Array values in expressions

```php
$expression = new Expression(
    'id IN (?)',
    [Argument::value([1, 2, 3, 4, 5])]
);
```

Produces:

### SQL output for array values

```sql
id IN (?, ?, ?, ?, ?)
```

### Nested expressions

```php
$innerExpression = new Expression('COUNT(*)');
$outerExpression = new Expression(
    'CASE WHEN ? > ? THEN ? ELSE ? END',
    [
        $innerExpression,
        Argument::value(10),
        Argument::literal('HIGH'),
        Argument::literal('LOW'),
    ]
);
```

Produces:

### SQL output for nested expressions

```sql
CASE WHEN COUNT(*) > 10 THEN HIGH ELSE LOW END
```

### Using database-specific functions

```php
use PhpDb\Sql\Predicate;

$select->where(new Predicate\Expression(
    'FIND_IN_SET(?, ?)',
    [
        Argument::value('admin'),
        Argument::identifier('roles'),
    ]
));
```

For detailed information on Arguments and Argument Types, see the [SQL Introduction](intro.md#arguments-and-argument-types).

## Combine (UNION, INTERSECT, EXCEPT)

The `Combine` class enables combining multiple SELECT statements using UNION,
INTERSECT, or EXCEPT operations.

### Basic Combine usage with UNION

```php
use PhpDb\Sql\Combine;

$select1 = $sql->select('table1')->where(['status' => 'active']);
$select2 = $sql->select('table2')->where(['status' => 'pending']);

$combine = new Combine($select1, Combine::COMBINE_UNION);
$combine->combine($select2);
```

### Combine API

```php
class Combine extends AbstractPreparableSql
{
    final public const COMBINE_UNION = 'union';
    final public const COMBINE_EXCEPT = 'except';
    final public const COMBINE_INTERSECT = 'intersect';

    public function __construct(
        Select|array|null $select = null,
        string $type = self::COMBINE_UNION,
        string $modifier = ''
    );
    public function combine(
        Select|array $select,
        string $type = self::COMBINE_UNION,
        string $modifier = ''
    ) : static;
    public function union(Select|array $select, string $modifier = '') : static;
    public function except(Select|array $select, string $modifier = '') : static;
    public function intersect(Select|array $select, string $modifier = '') : static;
    public function alignColumns() : static;
    public function getRawState(?string $key = null) : mixed;
}
```

### UNION

```php
$combine = new Combine();
$combine->union($select1);
$combine->union($select2, 'ALL'); // UNION ALL keeps duplicates
```

Produces:

### SQL output for UNION ALL

```sql
(SELECT * FROM table1 WHERE status = 'active')
UNION ALL
(SELECT * FROM table2 WHERE status = 'pending')
```

### EXCEPT

Returns rows from the first SELECT that don't appear in subsequent SELECTs:

```php
$allUsers = $sql->select('users')->columns(['id', 'email']);
$premiumUsers = $sql->select('premium_users')->columns(['user_id', 'email']);

$combine = new Combine();
$combine->union($allUsers);
$combine->except($premiumUsers);
```

### INTERSECT

Returns only rows that appear in all SELECT statements:

```php
$combine = new Combine();
$combine->union($select1);
$combine->intersect($select2);
```

### alignColumns()

Ensures all SELECT statements have the same column structure:

```php
$select1 = $sql->select('orders')->columns(['id', 'amount']);
$select2 = $sql->select('refunds')->columns(['id', 'amount', 'reason']);

$combine = new Combine();
$combine->union($select1);
$combine->union($select2);
$combine->alignColumns();
```

Produces:

### SQL output for aligned columns

```sql
(SELECT id, amount, NULL AS reason FROM orders)
UNION
(SELECT id, amount, reason FROM refunds)
```

## Platform-Specific Considerations

### Quote characters

Different databases use different quote characters. Let the platform handle quoting:

```php
// Correct - platform handles quoting
$select->from('users');

// Incorrect - manual quoting
$select->from('"users"');
```

### Identifier case sensitivity

Some databases are case-sensitive for identifiers. Be consistent:

```php
// Consistent naming
$select->from('UserAccounts')
    ->columns(['userId', 'userName']);
```

### NULL handling

NULL requires special handling in SQL:

```php
// Use IS NULL, not = NULL
$select->where->isNull('deleted_at');

// For NOT NULL
$select->where->isNotNull('email');
```

### Type-safe comparisons

When comparing identifiers to identifiers (not values):

```php
use PhpDb\Sql\Argument;

$where->equalTo(
    Argument::identifier('table1.column'),
    Argument::identifier('table2.column')
);
```