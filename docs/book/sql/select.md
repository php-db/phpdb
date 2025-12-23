# Select Queries

`PhpDb\Sql\Select` presents a unified API for building
platform-specific SQL SELECT queries. Instances may be created and
consumed without `PhpDb\Sql\Sql`:

## Creating a Select instance

```php
use PhpDb\Sql\Select;

$select = new Select();
// or, to produce a $select bound to a specific table
$select = new Select('foo');
```

If a table is provided to the `Select` object, then `from()` cannot be called
later to change the name of the table.

## Select API

Once you have a valid `Select` object, the following API can be used to
further specify various select statement parts:

```php title="Select class definition and constants"
class Select extends AbstractPreparableSql
    implements SqlInterface, PreparableSqlInterface
{
    final public const JOIN_INNER = 'inner';
    final public const JOIN_OUTER = 'outer';
    final public const JOIN_FULL_OUTER = 'full outer';
    final public const JOIN_LEFT = 'left';
    final public const JOIN_RIGHT = 'right';
    final public const JOIN_LEFT_OUTER = 'left outer';
    final public const JOIN_RIGHT_OUTER = 'right outer';
    final public const SQL_STAR = '*';
    final public const ORDER_ASCENDING = 'ASC';
    final public const ORDER_DESCENDING = 'DESC';
    final public const QUANTIFIER_DISTINCT = 'DISTINCT';
    final public const QUANTIFIER_ALL = 'ALL';
    final public const COMBINE_UNION = 'union';
    final public const COMBINE_EXCEPT = 'except';
    final public const COMBINE_INTERSECT = 'intersect';

    public Where $where;
    public Having $having;
    public Join $joins;

    public function __construct(
        array|string|TableIdentifier|null $table = null
    );
    public function from(
        array|string|TableIdentifier $table
    ) : static;
    public function quantifier(
        ExpressionInterface|string $quantifier
    ) : static;
    public function columns(
        array $columns,
        bool $prefixColumnsWithTable = true
    ) : static;
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = self::SQL_STAR,
        string $type = self::JOIN_INNER
    ) : static;
    public function where(
        PredicateInterface|array|string|Closure $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ) : self;
    public function group(mixed $group) : static;
    public function having(
        Having|PredicateInterface|array|Closure|string $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ) : static;
    public function order(
        ExpressionInterface|array|string $order
    ) : static;
    public function limit(int|string $limit) : static;
    public function offset(int|string $offset) : static;
    public function combine(
        Select $select,
        string $type = self::COMBINE_UNION,
        string $modifier = ''
    ) : static;
    public function reset(string $part) : static;
    public function getRawState(?string $key = null) : mixed;
    public function isTableReadOnly() : bool;
}
```

## from()

```php title="Specifying the FROM table"
// As a string:
$select->from('foo');

// As an array to specify an alias
// (produces SELECT "t".* FROM "table" AS "t")
$select->from(['t' => 'table']);

// Using a Sql\TableIdentifier:
// (same output as above)
$select->from(['t' => new TableIdentifier('table')]);
```

## columns()

```php title="Selecting columns"
// As an array of names
$select->columns(['foo', 'bar']);

// As an associative array with aliases as the keys
// (produces 'bar' AS 'foo', 'bax' AS 'baz')
$select->columns([
    'foo' => 'bar',
    'baz' => 'bax'
]);

// Sql function call on the column
// (produces CONCAT_WS('/', 'bar', 'bax') AS 'foo')
$select->columns([
    'foo' => new \PhpDb\Sql\Expression("CONCAT_WS('/', 'bar', 'bax')")
]);
```

## join()

```php title="Basic JOIN examples"
$select->join(
    'foo',              // table name
    'id = bar.id',      // expression to join on
    ['bar', 'baz'],     // (optional) list of columns
    $select::JOIN_OUTER // (optional), one of inner, outer, etc.
);

$select
    ->from(['f' => 'foo'])     // base table
    ->join(
        ['b' => 'bar'],        // join table with alias
        'f.foo_id = b.foo_id'  // join expression
    );
```

The `$on` parameter accepts either a string or a `PredicateInterface`
for complex join conditions:

```php title="JOIN with predicate conditions"
use PhpDb\Sql\Argument;
use PhpDb\Sql\Predicate;

$where = new Predicate\Predicate();
$where->equalTo(
        Argument::identifier('orders.customerId'),
        Argument::identifier('customers.id')
    )
    ->greaterThan('orders.amount', 100);

$select->from('customers')
    ->join('orders', $where, ['orderId', 'amount']);
```

Produces:

```sql
SELECT customers.*, orders.orderId, orders.amount
FROM customers
INNER JOIN orders
  ON orders.customerId = customers.id AND orders.amount > 100
```

## order()

```php title="Ordering results"
$select = new Select;
$select->order('id DESC'); // produces 'id' DESC

$select = new Select;
$select
    ->order('id DESC')
    // produces 'id' DESC, 'name' ASC, 'age' DESC
    ->order('name ASC, age DESC');

$select = new Select;
// produces 'name' ASC, 'age' DESC
$select->order(['name ASC', 'age DESC']);
```

## limit() and offset()

```php title="Limiting and offsetting results"
$select = new Select;
$select->limit(5);
$select->offset(10);
```

## group()

The `group()` method specifies columns for GROUP BY clauses,
typically used with aggregate functions to group rows that share
common values.

```php title="Grouping by a single column"
$select->group('category');
```

Multiple columns can be specified as an array,
or by calling `group()` multiple times:

```php title="Grouping by multiple columns"
$select->group(['category', 'status']);

$select->group('category')
    ->group('status');
```

As an example with aggregate functions:

```php title="Grouping with aggregate functions"
$select->from('orders')
    ->columns([
        'customer_id',
        'totalOrders' => new Expression('COUNT(*)'),
        'totalAmount' => new Expression('SUM(amount)'),
    ])
    ->group('customer_id');
```

Produces:

```sql
SELECT customer_id, COUNT(*) AS totalOrders, SUM(amount) AS totalAmount
FROM orders
GROUP BY customer_id
```

You can also use expressions in GROUP BY:

```php title="Grouping with expressions"
$select->from('orders')
    ->columns([
        'orderYear' => new Expression('YEAR(created_at)'),
        'orderCount' => new Expression('COUNT(*)'),
    ])
    ->group(new Expression('YEAR(created_at)'));
```

Produces:

```sql
SELECT YEAR(created_at) AS orderYear,
       COUNT(*) AS orderCount
FROM orders
GROUP BY YEAR(created_at)
```

## quantifier()

The `quantifier()` method applies a quantifier to the SELECT statement,
such as DISTINCT or ALL.

```php title="Using DISTINCT quantifier"
$select->from('orders')
    ->columns(['customer_id'])
    ->quantifier(Select::QUANTIFIER_DISTINCT);
```

Produces:

```sql
SELECT DISTINCT customer_id FROM orders
```

The `QUANTIFIER_ALL` constant explicitly specifies ALL,
though this is typically the default behavior:

```php title="Using ALL quantifier"
$select->quantifier(Select::QUANTIFIER_ALL);
```

## reset()

The `reset()` method allows you to clear specific parts of a Select
statement, useful when building queries dynamically.

```php title="Building a Select query before reset"
$select->from('users')
    ->columns(['id', 'name'])
    ->where(['status' => 'active'])
    ->order('created_at DESC')
    ->limit(10);
```

Before reset, produces:

```sql
SELECT id, name FROM users
WHERE status = 'active' ORDER BY created_at DESC LIMIT 10
```

After resetting WHERE, ORDER, and LIMIT:

```php title="Resetting specific parts of a query"
$select->reset(Select::WHERE);
$select->reset(Select::ORDER);
$select->reset(Select::LIMIT);
```

Produces:

```sql
SELECT id, name FROM users
```

Available parts that can be reset:

- `Select::QUANTIFIER`
- `Select::COLUMNS`
- `Select::JOINS`
- `Select::WHERE`
- `Select::GROUP`
- `Select::HAVING`
- `Select::LIMIT`
- `Select::OFFSET`
- `Select::ORDER`
- `Select::COMBINE`

Note that resetting `Select::TABLE` will throw an exception if the
table was provided in the constructor (read-only table).

## getRawState()

The `getRawState()` method returns the internal state of the Select
object, useful for debugging or introspection.

```php title="Getting the full raw state"
$state = $select->getRawState();
```

Returns an array containing:

```php title="Raw state array structure"
[
    'table' => 'users',
    'quantifier' => null,
    'columns' => ['id', 'name', 'email'],
    'joins' => Join object,
    'where' => Where object,
    'order' => ['created_at DESC'],
    'limit' => 10,
    'offset' => 0,
    'group' => null,
    'having' => null,
    'combine' => [],
]
```

You can also retrieve a specific state element:

```php title="Getting specific state elements"
$table = $select->getRawState(Select::TABLE);
$columns = $select->getRawState(Select::COLUMNS);
$limit = $select->getRawState(Select::LIMIT);
```

## Combine

For combining SELECT statements using UNION, INTERSECT, or EXCEPT,
see [Advanced SQL Features: Combine](advanced.md#combine-union-intersect-except).

Quick example:

```php
use PhpDb\Sql\Combine;

$select1 = $sql->select('table1')->where(['status' => 'active']);
$select2 = $sql->select('table2')->where(['status' => 'pending']);

$combine = new Combine();
$combine->union($select1);
$combine->union($select2, 'ALL');
```

## Advanced JOIN Usage

### Multiple JOIN types in a single query

```php title="Combining different JOIN types"
$select->from(['u' => 'users'])
    ->join(
        ['o' => 'orders'],
        'u.id = o.userId',
        ['orderId', 'amount'],
        Select::JOIN_LEFT
    )
    ->join(
        ['p' => 'products'],
        'o.productId = p.id',
        ['productName', 'price'],
        Select::JOIN_INNER
    )
    ->join(
        ['r' => 'reviews'],
        'p.id = r.productId',
        ['rating'],
        Select::JOIN_RIGHT
    );
```

### JOIN with no column selection

When you need to join a table only for filtering purposes without
selecting its columns:

```php title="Joining for filtering without selecting columns"
$select->from('orders')
    ->join('customers', 'orders.customerId = customers.id', [])
    ->where(['customers.status' => 'premium']);
```

Produces:

```sql
SELECT orders.* FROM orders
INNER JOIN customers ON orders.customerId = customers.id
WHERE customers.status = 'premium'
```

### JOIN with expressions in columns

```php title="Using expressions in JOIN column selection"
$select->from('users')
    ->join(
        'orders',
        'users.id = orders.userId',
        [
            'orderCount' => new Expression('COUNT(*)'),
            'totalSpent' => new Expression('SUM(amount)'),
        ]
    );
```

### Accessing the Join object

The Join object can be accessed directly for programmatic manipulation:

```php title="Programmatically accessing Join information"
foreach ($select->joins as $join) {
    $tableName = $join['name'];
    $onCondition = $join['on'];
    $columns = $join['columns'];
    $joinType = $join['type'];
}

$joinCount = count($select->joins);

$allJoins = $select->joins->getJoins();

$select->joins->reset();
```
