# SQL Abstraction

`PhpDb\Sql` is a SQL abstraction layer for building platform-specific SQL
queries via an object-oriented API. The end result of a `PhpDb\Sql` object
will be to either produce a `Statement` and `ParameterContainer` that
represents the target query, or a full string that can be directly executed
against the database platform. To achieve this, `PhpDb\Sql` objects require a
`PhpDb\Adapter\Adapter` object in order to produce the desired results.

## Quick start

There are four primary tasks associated with interacting with a database
defined by Data Manipulation Language (DML): selecting, inserting, updating,
and deleting. As such, there are four primary classes that developers can
interact with in order to build queries in the `PhpDb\Sql` namespace:
`Select`, `Insert`, `Update`, and `Delete`.

Since these four tasks are so closely related and generally used together
within the same application, the `PhpDb\Sql\Sql` class helps you create them
and produce the result you are attempting to achieve.

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter);
$select = $sql->select(); // returns a PhpDb\Sql\Select instance
$insert = $sql->insert(); // returns a PhpDb\Sql\Insert instance
$update = $sql->update(); // returns a PhpDb\Sql\Update instance
$delete = $sql->delete(); // returns a PhpDb\Sql\Delete instance
```

As a developer, you can now interact with these objects, as described in the
sections below, to customize each query. Once they have been populated with
values, they are ready to either be prepared or executed.

To prepare (using a Select object):

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter);
$select = $sql->select();
$select->from('foo');
$select->where(['id' => 2]);

$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();
```

To execute (using a Select object)

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter);
$select = $sql->select();
$select->from('foo');
$select->where(['id' => 2]);

$selectString = $sql->buildSqlString($select);
$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
```

`PhpDb\\Sql\\Sql` objects can also be bound to a particular table so that in
obtaining a `Select`, `Insert`, `Update`, or `Delete` instance, the object will be
seeded with the table:

```php
use PhpDb\Sql\Sql;

$sql    = new Sql($adapter, 'foo');
$select = $sql->select();
$select->where(['id' => 2]); // $select already has from('foo') applied
```

## Common interfaces for SQL implementations

Each of these objects implements the following two interfaces:

```php
interface PreparableSqlInterface
{
     public function prepareStatement(Adapter $adapter, StatementInterface $statement) : void;
}

interface SqlInterface
{
     public function getSqlString(PlatformInterface $adapterPlatform = null) : string;
}
```

Use these functions to produce either (a) a prepared statement, or (b) a string
to execute.

## Select

`PhpDb\Sql\Select` presents a unified API for building platform-specific SQL
SELECT queries. Instances may be created and consumed without
`PhpDb\Sql\Sql`:

```php
use PhpDb\Sql\Select;

$select = new Select();
// or, to produce a $select bound to a specific table
$select = new Select('foo');
```

If a table is provided to the `Select` object, then `from()` cannot be called
later to change the name of the table.

Once you have a valid `Select` object, the following API can be used to further
specify various select statement parts:

```php
class Select extends AbstractSql implements SqlInterface, PreparableSqlInterface
{
    const JOIN_INNER = 'inner';
    const JOIN_OUTER = 'outer';
    const JOIN_FULL_OUTER  = 'full outer';
    const JOIN_LEFT = 'left';
    const JOIN_RIGHT = 'right';
    const SQL_STAR = '*';
    const ORDER_ASCENDING = 'ASC';
    const ORDER_DESCENDING = 'DESC';

    public $where; // @param Where $where

    public function __construct(string|array|TableIdentifier $table = null);
    public function from(string|array|TableIdentifier $table) : self;
    public function columns(array $columns, bool $prefixColumnsWithTable = true) : self;
    public function join(string|array|TableIdentifier $name, string $on, string|array $columns = self::SQL_STAR, string $type = self::JOIN_INNER) : self;
    public function where(Where|callable|string|array|PredicateInterface $predicate, string $combination = Predicate\PredicateSet::OP_AND) : self;
    public function group(string|array $group);
    public function having(Having|callable|string|array $predicate, string $combination = Predicate\PredicateSet::OP_AND) : self;
    public function order(string|array $order) : self;
    public function limit(int $limit) : self;
    public function offset(int $offset) : self;
}
```

### from()

```php
// As a string:
$select->from('foo');

// As an array to specify an alias
// (produces SELECT "t".* FROM "table" AS "t")
$select->from(['t' => 'table']);

// Using a Sql\TableIdentifier:
// (same output as above)
$select->from(['t' => new TableIdentifier('table')]);
```

### columns()

```php
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

### join()

```php
$select->join(
    'foo',              // table name
    'id = bar.id',      // expression to join on (will be quoted by platform object before insertion),
    ['bar', 'baz'],     // (optional) list of columns, same requirements as columns() above
    $select::JOIN_OUTER // (optional), one of inner, outer, full outer, left, right also represented by constants in the API
);

$select
    ->from(['f' => 'foo'])     // base table
    ->join(
        ['b' => 'bar'],        // join table with alias
        'f.foo_id = b.foo_id'  // join expression
    );
```

The `$on` parameter accepts either a string or a `PredicateInterface` for complex join conditions:

```php
use PhpDb\Sql\Predicate;

$where = new Predicate\Predicate();
$where->equalTo('orders.customerId', 'customers.id', Predicate\Predicate::TYPE_IDENTIFIER, Predicate\Predicate::TYPE_IDENTIFIER)
    ->greaterThan('orders.amount', 100);

$select->from('customers')
    ->join('orders', $where, ['orderId', 'amount']);
```

Produces:

```sql
SELECT customers.*, orders.orderId, orders.amount
FROM customers
INNER JOIN orders ON orders.customerId = customers.id AND orders.amount > 100
```

### where(), having()

`PhpDb\Sql\Select` provides bit of flexibility as it regards to what kind of
parameters are acceptable when calling `where()` or `having()`. The method
signature is listed as:

```php
/**
 * Create where clause
 *
 * @param  Where|callable|string|array $predicate
 * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
 * @return Select
 */
public function where($predicate, $combination = Predicate\PredicateSet::OP_AND);
```

If you provide a `PhpDb\Sql\Where` instance to `where()` or a
`PhpDb\Sql\Having` instance to `having()`, any previous internal instances
will be replaced completely. When either instance is processed, this object will
be iterated to produce the WHERE or HAVING section of the SELECT statement.

If you provide a PHP callable to `where()` or `having()`, this function will be
called with the `Select`'s `Where`/`Having` instance as the only parameter.
This enables code like the following:

```php
$select->where(function (Where $where) {
    $where->like('username', 'ralph%');
});
```

If you provide a *string*, this string will be used to create a
`PhpDb\Sql\Predicate\Expression` instance, and its contents will be applied
as-is, with no quoting:

```php
// SELECT "foo".* FROM "foo" WHERE x = 5
$select->from('foo')->where('x = 5');
```

If you provide an array with integer indices, the value can be one of:

- a string; this will be used to build a `Predicate\Expression`.
- any object implementing `Predicate\PredicateInterface`.

In either case, the instances are pushed onto the `Where` stack with the
`$combination` provided (defaulting to `AND`).

As an example:

```php
// SELECT "foo".* FROM "foo" WHERE x = 5 AND y = z
$select->from('foo')->where(['x = 5', 'y = z']);
```

If you provide an associative array with string keys, any value with a string
key will be cast as follows:

| PHP value | Predicate type                                         |
|-----------|--------------------------------------------------------|
| `null`    | `Predicate\IsNull`                                     |
| `array`   | `Predicate\In`                                         |
| `string`  | `Predicate\Operator`, where the key is the identifier. |

As an example:

```php
// SELECT "foo".* FROM "foo" WHERE "c1" IS NULL AND "c2" IN (?, ?, ?) AND "c3" IS NOT NULL
$select->from('foo')->where([
    'c1' => null,
    'c2' => [1, 2, 3],
    new \PhpDb\Sql\Predicate\IsNotNull('c3'),
]);
```

As another example of complex queries with nested conditions e.g.

```sql
SELECT * WHERE (column1 is null or column1 = 2) AND (column2 = 3)
```

you need to use the `nest()` and `unnest()` methods, as follows:

```php
$select->where->nest() // bracket opened
    ->isNull('column1')
    ->or
    ->equalTo('column1', '2')
    ->unnest();  // bracket closed
    ->equalTo('column2', '3');
```

### order()

```php
$select = new Select;
$select->order('id DESC'); // produces 'id' DESC

$select = new Select;
$select
    ->order('id DESC')
    ->order('name ASC, age DESC'); // produces 'id' DESC, 'name' ASC, 'age' DESC

$select = new Select;
$select->order(['name ASC', 'age DESC']); // produces 'name' ASC, 'age' DESC
```

### limit() and offset()

```php
$select = new Select;
$select->limit(5);
$select->offset(10);
```

### group()

The `group()` method specifies columns for GROUP BY clauses, typically used with
aggregate functions to group rows that share common values.

```php
$select->group('category');
```

Multiple columns can be specified as an array, or by calling `group()` multiple times:

```php
$select->group(['category', 'status']);

$select->group('category')
    ->group('status');
```

As an example with aggregate functions:

```php
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

```php
$select->from('orders')
    ->columns([
        'orderYear' => new Expression('YEAR(created_at)'),
        'orderCount' => new Expression('COUNT(*)'),
    ])
    ->group(new Expression('YEAR(created_at)'));
```

Produces:

```sql
SELECT YEAR(created_at) AS orderYear, COUNT(*) AS orderCount
FROM orders
GROUP BY YEAR(created_at)
```

### quantifier()

The `quantifier()` method applies a quantifier to the SELECT statement, such as
DISTINCT or ALL.

```php
$select->from('orders')
    ->columns(['customer_id'])
    ->quantifier(Select::QUANTIFIER_DISTINCT);
```

Produces:

```sql
SELECT DISTINCT customer_id FROM orders
```

The `QUANTIFIER_ALL` constant explicitly specifies ALL, though this is typically
the default behavior:

```php
$select->quantifier(Select::QUANTIFIER_ALL);
```

### reset()

The `reset()` method allows you to clear specific parts of a Select statement,
useful when building queries dynamically.

```php
$select->from('users')
    ->columns(['id', 'name'])
    ->where(['status' => 'active'])
    ->order('created_at DESC')
    ->limit(10);
```

Before reset, produces:

```sql
SELECT id, name FROM users WHERE status = 'active' ORDER BY created_at DESC LIMIT 10
```

After resetting WHERE, ORDER, and LIMIT:

```php
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

Note that resetting `Select::TABLE` will throw an exception if the table was
provided in the constructor (read-only table).

### getRawState()

The `getRawState()` method returns the internal state of the Select object,
useful for debugging or introspection.

```php
$state = $select->getRawState();
```

Returns an array containing:

```php
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

```php
$table = $select->getRawState(Select::TABLE);
$columns = $select->getRawState(Select::COLUMNS);
$limit = $select->getRawState(Select::LIMIT);
```

## Combine

The `Combine` class enables combining multiple SELECT statements using UNION,
INTERSECT, or EXCEPT operations. Each operation can optionally include modifiers
such as ALL or DISTINCT.

```php
use PhpDb\Sql\Combine;

$select1 = $sql->select('table1')->where(['status' => 'active']);
$select2 = $sql->select('table2')->where(['status' => 'pending']);

$combine = new Combine($select1, Combine::COMBINE_UNION);
$combine->combine($select2);
```

### UNION

The `union()` method combines results from multiple SELECT statements, removing
duplicates by default.

```php
$combine = new Combine();
$combine->union($select1);
$combine->union($select2, 'ALL');
```

Produces:

```sql
(SELECT * FROM table1 WHERE status = 'active')
UNION ALL
(SELECT * FROM table2 WHERE status = 'pending')
```

### EXCEPT

The `except()` method returns rows from the first SELECT that do not appear in
subsequent SELECT statements.

```php
$combine = new Combine();
$combine->union($select1);
$combine->except($select2);
```

### INTERSECT

The `intersect()` method returns only rows that appear in all SELECT statements.

```php
$combine = new Combine();
$combine->union($select1);
$combine->intersect($select2);
```

### alignColumns()

The `alignColumns()` method ensures all SELECT statements have the same column
structure by adding NULL for missing columns.

```php
$select1 = $sql->select('orders')->columns(['id', 'amount']);
$select2 = $sql->select('refunds')->columns(['id', 'amount', 'reason']);

$combine = new Combine();
$combine->union($select1);
$combine->union($select2);
$combine->alignColumns();
```

Produces:

```sql
(SELECT id, amount, NULL AS reason FROM orders)
UNION
(SELECT id, amount, reason FROM refunds)
```

After alignment, both SELECTs will have id, amount, and reason columns, with
NULL used where columns are missing.

### Using combine() on Select

The Select class also provides a `combine()` method for simple combinations:

```php
$select1->combine($select2, Select::COMBINE_UNION, 'ALL');
```

Note that Select can only combine with one other Select. For multiple
combinations, use the Combine class directly.

## Insert

The Insert API:

```php
class Insert implements SqlInterface, PreparableSqlInterface
{
    const VALUES_MERGE = 'merge';
    const VALUES_SET   = 'set';

    public function __construct(string|TableIdentifier $table = null);
    public function into(string|TableIdentifier $table) : self;
    public function columns(array $columns) : self;
    public function values(array $values, string $flag = self::VALUES_SET) : self;
}
```

As with `Select`, the table may be provided during instantiation or via the
`into()` method.

### columns()

```php
$insert->columns(['foo', 'bar']); // set the valid columns
```

### values()

The default behavior of values is to set the values. Successive calls will not
preserve values from previous calls.

```php
$insert->values([
    'col_1' => 'value1',
    'col_2' => 'value2',
]);
```

To merge values with previous calls, provide the appropriate flag:
`PhpDb\Sql\Insert::VALUES_MERGE`

```php
$insert->values(['col_2' => 'value2'], $insert::VALUES_MERGE);
```

### select()

The `select()` method enables INSERT INTO ... SELECT statements, copying data
from one table to another.

```php
$select = $sql->select('tempUsers')
    ->columns(['username', 'email', 'createdAt'])
    ->where(['imported' => false]);

$insert = $sql->insert('users');
$insert->columns(['username', 'email', 'createdAt']);
$insert->select($select);
```

Produces:

```sql
INSERT INTO users (username, email, createdAt)
SELECT username, email, createdAt FROM tempUsers WHERE imported = 0
```

Alternatively, you can pass the Select object directly to `values()`:

```php
$insert->values($select);
```

Important: The column order must match between INSERT columns and SELECT columns.

### Property-style column access

The Insert class supports property-style access to columns as an alternative to
using `values()`:

```php
$insert = $sql->insert('users');
$insert->name = 'John';
$insert->email = 'john@example.com';

if (isset($insert->name)) {
    $value = $insert->name;
}

unset($insert->email);
```

This is equivalent to:

```php
$insert->values([
    'name' => 'John',
    'email' => 'john@example.com',
]);
```

## InsertIgnore

The `InsertIgnore` class provides MySQL-specific INSERT IGNORE syntax, which
silently ignores rows that would cause duplicate key errors.

```php
use PhpDb\Sql\InsertIgnore;

$insert = new InsertIgnore('users');
$insert->values([
    'username' => 'john',
    'email' => 'john@example.com',
]);
```

Produces:

```sql
INSERT IGNORE INTO users (username, email) VALUES (?, ?)
```

If a row with the same username or email already exists and there is a unique
constraint, the insert will be silently skipped rather than producing an error.

Note: INSERT IGNORE is MySQL-specific. Other databases may use different syntax
for this behavior (e.g., INSERT ... ON CONFLICT DO NOTHING in PostgreSQL).

## Update

```php
class Update
{
    const VALUES_MERGE = 'merge';
    const VALUES_SET   = 'set';

    public $where; // @param Where $where
    public function __construct(string|TableIdentifier $table = null);
    public function table(string|TableIdentifier $table) : self;
    public function set(array $values, string $flag = self::VALUES_SET) : self;
    public function where(Where|callable|string|array|PredicateInterface $predicate, string $combination = Predicate\PredicateSet::OP_AND) : self;
}
```

### set()

```php
$update->set(['foo' => 'bar', 'baz' => 'bax']);
```

The `set()` method accepts a flag parameter to control merging behavior:

```php
$update->set(['status' => 'active'], Update::VALUES_SET);
$update->set(['updatedAt' => new Expression('NOW()')], Update::VALUES_MERGE);
```

When using `VALUES_MERGE`, you can optionally specify a numeric priority to control the order of SET clauses:

```php
$update->set(['counter' => 1], 100);
$update->set(['status' => 'pending'], 50);
$update->set(['flag' => true], 75);
```

Produces SET clauses in priority order (50, 75, 100):

```sql
UPDATE table SET status = ?, flag = ?, counter = ?
```

This is useful when the order of SET operations matters for certain database operations or triggers.

### where()

See the [section on Where and Having](#where-and-having).

## Delete

```php
class Delete
{
    public $where; // @param Where $where

    public function __construct(string|TableIdentifier $table = null);
    public function from(string|TableIdentifier $table);
    public function where(Where|callable|string|array|PredicateInterface $predicate, string $combination = Predicate\PredicateSet::OP_AND) : self;
}
```

### where()

See the [section on Where and Having](#where-and-having).

## Where and Having

In the following, we will talk about `Where`; note, however, that `Having`
utilizes the same API.

Effectively, `Where` and `Having` extend from the same base object, a
`Predicate` (and `PredicateSet`). All of the parts that make up a WHERE or
HAVING clause that are AND'ed or OR'd together are called *predicates*.  The
full set of predicates is called a `PredicateSet`. A `Predicate` generally
contains the values (and identifiers) separate from the fragment they belong to
until the last possible moment when the statement is either prepared
(parameteritized) or executed. In parameterization, the parameters will be
replaced with their proper placeholder (a named or positional parameter), and
the values stored inside an `Adapter\ParameterContainer`. When executed, the
values will be interpolated into the fragments they belong to and properly
quoted.

In the `Where`/`Having` API, a distinction is made between what elements are
considered identifiers (`TYPE_IDENTIFIER`) and which are values (`TYPE_VALUE`).
There is also a special use case type for literal values (`TYPE_LITERAL`). All
element types are expressed via the `PhpDb\Sql\ExpressionInterface`
interface.

> **Note:** The `TYPE_*` constants are legacy constants maintained for backward
> compatibility. New code should use the `ArgumentType` enum and `Argument`
> class for type-safe argument handling (see the section below).

### Arguments and Argument Types

`PhpDb\Sql` provides the `Argument` class along with the `ArgumentType` enum
for type-safe specification of SQL values. This provides a modern,
object-oriented alternative to using raw values or the legacy type constants.

The `ArgumentType` enum defines four types:

- `ArgumentType::Identifier` - For column names, table names, and other identifiers that should be quoted
- `ArgumentType::Value` - For values that should be parameterized or properly escaped (default)
- `ArgumentType::Literal` - For literal SQL fragments that should not be quoted or escaped
- `ArgumentType::Select` - For subqueries (automatically detected when using Expression or SqlInterface objects)

```php
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;

// Using the constructor with explicit type
$arg = new Argument('column_name', ArgumentType::Identifier);

// Using static factory methods (recommended)
$valueArg = Argument::value(123);           // Value type
$identifierArg = Argument::identifier('id'); // Identifier type
$literalArg = Argument::literal('NOW()');   // Literal SQL

// Using array notation for type specification
$arg = new Argument(['column_name' => ArgumentType::Identifier]);

// Arrays of values are also supported
$arg = new Argument([1, 2, 3], ArgumentType::Value);
```

The `Argument` class is particularly useful when working with expressions
where you need to explicitly control how values are treated:

```php
use PhpDb\Sql\Expression;
use PhpDb\Sql\Argument;

// Without Argument - relies on positional type inference
$expression = new Expression(
    'CONCAT(?, ?, ?)',
    [
        ['column1' => ExpressionInterface::TYPE_IDENTIFIER],
        ['-' => ExpressionInterface::TYPE_VALUE],
        ['column2' => ExpressionInterface::TYPE_IDENTIFIER]
    ]
);

// With Argument - more explicit and readable
$expression = new Expression(
    'CONCAT(?, ?, ?)',
    [
        Argument::identifier('column1'),
        Argument::value('-'),
        Argument::identifier('column2')
    ]
);
```

> ### Literals
>
> `PhpDb\Sql` makes the distinction that literals will not have any parameters
> that need interpolating, while `Expression` objects *might* have parameters
> that need interpolating. In cases where there are parameters in an `Expression`,
> `PhpDb\Sql\AbstractSql` will do its best to identify placeholders when the
> `Expression` is processed during statement creation. In short, if you don't
> have parameters, use `Literal` objects or `Argument::literal()`.

The `Where` and `Having` API is that of `Predicate` and `PredicateSet`:

```php
// Where & Having extend Predicate:
class Predicate extends PredicateSet
{
    public $and;
    public $or;
    public $AND;
    public $OR;
    public $NEST;
    public $UNNEST;

    public function nest() : Predicate;
    public function setUnnest(Predicate $predicate) : void;
    public function unnest() : Predicate;
    public function equalTo(
        int|float|bool|string $left,
        int|float|bool|string $right,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    ) : self;
    public function notEqualTo(
        int|float|bool|string $left,
        int|float|bool|string $right,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    ) : self;
    public function lessThan(
        int|float|bool|string $left,
        int|float|bool|string $right,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    ) : self;
    public function greaterThan(
        int|float|bool|string $left,
        int|float|bool|string $right,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    ) : self;
    public function lessThanOrEqualTo(
        int|float|bool|string $left,
        int|float|bool|string $right,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    ) : self;
    public function greaterThanOrEqualTo(
        int|float|bool|string $left,
        int|float|bool|string $right,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    ) : self;
    public function like(string $identifier, string $like) : self;
    public function notLike(string $identifier, string $notLike) : self;
    public function literal(string $literal) : self;
    public function expression(string $expression, array $parameters = null) : self;
    public function isNull(string $identifier) : self;
    public function isNotNull(string $identifier) : self;
    public function in(string $identifier, array $valueSet = []) : self;
    public function notIn(string $identifier, array $valueSet = []) : self;
    public function between(
        string $identifier,
        int|float|string $minValue,
        int|float|string $maxValue
    ) : self;
    public function notBetween(
        string $identifier,
        int|float|string $minValue,
        int|float|string $maxValue
    ) : self;
    public function predicate(PredicateInterface $predicate) : self;

    // Inherited From PredicateSet

    public function addPredicate(PredicateInterface $predicate, $combination = null) : self;
    public function getPredicates() PredicateInterface[];
    public function orPredicate(PredicateInterface $predicate) : self;
    public function andPredicate(PredicateInterface $predicate) : self;
    public function getExpressionData() : array;
    public function count() : int;
}
```

Each method in the API will produce a corresponding `Predicate` object of a similarly named
type, as described below.

### equalTo(), lessThan(), greaterThan(), lessThanOrEqualTo(), greaterThanOrEqualTo()

```php
$where->equalTo('id', 5);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Operator($left, Operator::OPERATOR_EQUAL_TO, $right, $leftType, $rightType)
);
```

Operators use the following API:

```php
class Operator implements PredicateInterface
{
    const OPERATOR_EQUAL_TO                  = '=';
    const OP_EQ                              = '=';
    const OPERATOR_NOT_EQUAL_TO              = '!=';
    const OP_NE                              = '!=';
    const OPERATOR_LESS_THAN                 = '<';
    const OP_LT                              = '<';
    const OPERATOR_LESS_THAN_OR_EQUAL_TO     = '<=';
    const OP_LTE                             = '<=';
    const OPERATOR_GREATER_THAN              = '>';
    const OP_GT                              = '>';
    const OPERATOR_GREATER_THAN_OR_EQUAL_TO  = '>=';
    const OP_GTE                             = '>=';

    public function __construct(
        int|float|bool|string $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        int|float|bool|string $right = null,
        string $leftType = self::TYPE_IDENTIFIER,
        string $rightType = self::TYPE_VALUE
    );
    public function setLeft(int|float|bool|string $left);
    public function getLeft() : int|float|bool|string;
    public function setLeftType(string $type) : self;
    public function getLeftType() : string;
    public function setOperator(string $operator);
    public function getOperator() : string;
    public function setRight(int|float|bool|string $value) : self;
    public function getRight() : int|float|bool|string;
    public function setRightType(string $type) : self;
    public function getRightType() : string;
    public function getExpressionData() : array;
}
```

### like($identifier, $like), notLike($identifier, $notLike)

```php
$where->like($identifier, $like):

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Like($identifier, $like)
);
```

The following is the `Like` API:

```php
class Like implements PredicateInterface
{
    public function __construct(string $identifier = null, string $like = null);
    public function setIdentifier(string $identifier) : self;
    public function getIdentifier() : string;
    public function setLike(string $like) : self;
    public function getLike() : string;
}
```

### literal($literal)

```php
$where->literal($literal);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Literal($literal)
);
```

The following is the `Literal` API:

```php
class Literal implements ExpressionInterface, PredicateInterface
{
    const PLACEHOLDER = '?';
    public function __construct(string $literal = '');
    public function setLiteral(string $literal) : self;
    public function getLiteral() : string;
}
```

### expression($expression, $parameter)

```php
$where->expression($expression, $parameter);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Expression($expression, $parameter)
);
```

The following is the `Expression` API:

```php
class Expression implements ExpressionInterface, PredicateInterface
{
    const PLACEHOLDER = '?';

    public function __construct(
        string $expression = null,
        int|float|bool|string|array $valueParameter = null
        /* [, $valueParameter, ...  ] */
    );
    public function setExpression(string $expression) : self;
    public function getExpression() : string;
    public function setParameters(int|float|bool|string|array $parameters) : self;
    public function getParameters() : array;
}
```

Expression parameters can be supplied either as a single scalar, an array of values, or as an array of value/types for more granular escaping.

```php
$select
    ->from('foo')
    ->columns([
        new Expression(
            '(COUNT(?) + ?) AS ?',
            [
                ['some_column' => ExpressionInterface::TYPE_IDENTIFIER],
                [5 => ExpressionInterface::TYPE_VALUE],
                ['bar' => ExpressionInterface::TYPE_IDENTIFIER],
            ],
        ),
    ]);

// Produces SELECT (COUNT("some_column") + '5') AS "bar" FROM "foo"
```

### isNull($identifier)

```php
$where->isNull($identifier);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\IsNull($identifier)
);
```

The following is the `IsNull` API:

```php
class IsNull implements PredicateInterface
{
    public function __construct(string $identifier = null);
    public function setIdentifier(string $identifier) : self;
    public function getIdentifier() : string;
}
```

### isNotNull($identifier)

```php
$where->isNotNull($identifier);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\IsNotNull($identifier)
);
```

The following is the `IsNotNull` API:

```php
class IsNotNull implements PredicateInterface
{
    public function __construct(string $identifier = null);
    public function setIdentifier(string $identifier) : self;
    public function getIdentifier() : string;
}
```

### in($identifier, $valueSet), notIn($identifier, $valueSet)

```php
$where->in($identifier, $valueSet);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\In($identifier, $valueSet)
);
```

The following is the `In` API:

```php
class In implements PredicateInterface
{
    public function __construct(
        string|array $identifier = null,
        array|Select $valueSet = null
    );
    public function setIdentifier(string|array $identifier) : self;
    public function getIdentifier() : string|array;
    public function setValueSet(array|Select $valueSet) : self;
    public function getValueSet() : array|Select;
}
```

### between($identifier, $minValue, $maxValue), notBetween($identifier, $minValue, $maxValue)

```php
$where->between($identifier, $minValue, $maxValue);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Between($identifier, $minValue, $maxValue)
);
```

The following is the `Between` API:

```php
class Between implements PredicateInterface
{
    public function __construct(
        string $identifier = null,
        int|float|string $minValue = null,
        int|float|string $maxValue = null
    );
    public function setIdentifier(string $identifier) : self;
    public function getIdentifier() : string;
    public function setMinValue(int|float|string $minValue) : self;
    public function getMinValue() : int|float|string;
    public function setMaxValue(int|float|string $maxValue) : self;
    public function getMaxValue() : int|float|string;
    public function setSpecification(string $specification);
}
```

As an example with different value types:

```php
$where->between('age', 18, 65);
$where->notBetween('price', 100, 500);
$where->between('createdAt', '2024-01-01', '2024-12-31');
```

Produces:

```sql
WHERE age BETWEEN 18 AND 65 AND price NOT BETWEEN 100 AND 500 AND createdAt BETWEEN '2024-01-01' AND '2024-12-31'
```

Expressions can also be used:

```php
$where->between(new Expression('YEAR(createdAt)'), 2020, 2024);
```

Produces:

```sql
WHERE YEAR(createdAt) BETWEEN 2020 AND 2024
```

## Advanced Predicate Usage

### Magic properties for fluent chaining

The Predicate class provides magic properties that enable fluent method chaining
for combining predicates. These properties (`and`, `or`, `AND`, `OR`, `nest`,
`unnest`, `NEST`, `UNNEST`) facilitate readable query construction.

```php
$select->where
    ->equalTo('status', 'active')
    ->and
    ->greaterThan('age', 18)
    ->or
    ->equalTo('role', 'admin');
```

Produces:

```sql
WHERE status = 'active' AND age > 18 OR role = 'admin'
```

The properties are case-insensitive for convenience:

```php
$where->and->equalTo('a', 1);
$where->AND->equalTo('b', 2');
```

### Deep nesting of predicates

Complex nested conditions can be created using `nest()` and `unnest()`:

```php
$select->where->nest()
        ->nest()
            ->equalTo('a', 1)
            ->or
            ->equalTo('b', 2)
        ->unnest()
        ->and
        ->nest()
            ->equalTo('c', 3)
            ->or
            ->equalTo('d', 4)
        ->unnest()
    ->unnest();
```

Produces:

```sql
WHERE ((a = 1 OR b = 2) AND (c = 3 OR d = 4))
```

###  addPredicates() intelligent handling

The `addPredicates()` method from `PredicateSet` provides intelligent handling of
various input types, automatically creating appropriate predicate objects based on
the input.

```php
$where->addPredicates([
    'status = "active"',
    'age > ?',
    'category' => null,
    'id' => [1, 2, 3],
    'name' => 'John',
    new \PhpDb\Sql\Predicate\IsNotNull('email'),
]);
```

The method detects and handles:

| Input Type | Behavior |
|------------|----------|
| String without `?` | Creates `Literal` predicate |
| String with `?` | Creates `Expression` predicate (requires parameters) |
| Key => `null` | Creates `IsNull` predicate |
| Key => array | Creates `In` predicate |
| Key => scalar | Creates `Operator` predicate (equality) |
| `PredicateInterface` | Uses predicate directly |

Combination operators can be specified:

```php
$where->addPredicates([
    'role' => 'admin',
    'status' => 'active',
], PredicateSet::OP_OR);
```

Produces:

```sql
WHERE role = 'admin' OR status = 'active'
```

### Using LIKE and NOT LIKE patterns

The `like()` and `notLike()` methods support SQL wildcard patterns:

```php
$where->like('name', 'John%');
$where->like('email', '%@gmail.com');
$where->like('description', '%keyword%');
$where->notLike('email', '%@spam.com');
```

Multiple LIKE conditions:

```php
$where->like('name', 'A%')
    ->or
    ->like('name', 'B%');
```

Produces:

```sql
WHERE name LIKE 'A%' OR name LIKE 'B%'
```

### Using HAVING with aggregate functions

While `where()` filters rows before grouping, `having()` filters groups after
aggregation. The HAVING clause is used with GROUP BY and aggregate functions.

```php
$select->from('orders')
    ->columns([
        'customerId',
        'orderCount' => new Expression('COUNT(*)'),
        'totalAmount' => new Expression('SUM(amount)'),
    ])
    ->where->greaterThan('amount', 0)
    ->group('customerId')
    ->having->greaterThan(new Expression('COUNT(*)'), 10)
    ->having->greaterThan(new Expression('SUM(amount)'), 1000);
```

Produces:

```sql
SELECT customerId, COUNT(*) AS orderCount, SUM(amount) AS totalAmount
FROM orders
WHERE amount > 0
GROUP BY customerId
HAVING COUNT(*) > 10 AND SUM(amount) > 1000
```

Using closures with HAVING:

```php
$select->having(function ($having) {
    $having->greaterThan(new Expression('AVG(rating)'), 4.5)
        ->or
        ->greaterThan(new Expression('COUNT(reviews)'), 100);
});
```

Produces:

```sql
HAVING AVG(rating) > 4.5 OR COUNT(reviews) > 100
```

## Subqueries

Subqueries can be used in various contexts within SQL statements, including WHERE
clauses, FROM clauses, and SELECT columns.

### Subqueries in WHERE IN clauses

```php
$subselect = $sql->select('orders')
    ->columns(['customerId'])
    ->where(['status' => 'completed']);

$select = $sql->select('customers')
    ->where->in('id', $subselect);
```

Produces:

```sql
SELECT customers.* FROM customers
WHERE id IN (SELECT customerId FROM orders WHERE status = 'completed')
```

### Subqueries in FROM clauses

```php
$subselect = $sql->select('orders')
    ->columns([
        'customerId',
        'total' => new Expression('SUM(amount)'),
    ])
    ->group('customerId');

$select = $sql->select(['orderTotals' => $subselect])
    ->where->greaterThan('orderTotals.total', 1000);
```

Produces:

```sql
SELECT orderTotals.* FROM
(SELECT customerId, SUM(amount) AS total FROM orders GROUP BY customerId) AS orderTotals
WHERE orderTotals.total > 1000
```

### Scalar subqueries in SELECT columns

```php
$subselect = $sql->select('orders')
    ->columns([new Expression('COUNT(*)')])
    ->where(new Predicate\Expression('orders.customerId = customers.id'));

$select = $sql->select('customers')
    ->columns([
        'id',
        'name',
        'orderCount' => $subselect,
    ]);
```

Produces:

```sql
SELECT id, name,
(SELECT COUNT(*) FROM orders WHERE orders.customerId = customers.id) AS orderCount
FROM customers
```

### Subqueries with comparison operators

```php
$subselect = $sql->select('orders')
    ->columns([new Expression('AVG(amount)')]);

$select = $sql->select('orders')
    ->where->greaterThan('amount', $subselect);
```

Produces:

```sql
SELECT orders.* FROM orders
WHERE amount > (SELECT AVG(amount) FROM orders)
```

## Advanced JOIN Usage

### Multiple JOIN types in a single query

```php
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

When you need to join a table only for filtering purposes without selecting its
columns:

```php
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

```php
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

```php
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

## Update and Delete Safety Features

Both Update and Delete classes include empty WHERE protection by default, which
prevents accidental mass updates or deletes.

```php
$update = $sql->update('users');
$update->set(['status' => 'deleted']);

$state = $update->getRawState();
$protected = $state['emptyWhereProtection'];
```

Most database drivers will prevent execution of UPDATE or DELETE statements
without a WHERE clause when this protection is enabled. Always include a WHERE
clause:

```php
$update->where(['id' => 123]);

$delete = $sql->delete('logs');
$delete->where->lessThan('createdAt', '2020-01-01');
```

### Update with JOIN

The Update class supports JOIN clauses for multi-table updates:

```php
$update = $sql->update('orders');
$update->set(['status' => 'cancelled']);
$update->join('customers', 'orders.customerId = customers.id', Update\Join::JOIN_INNER);
$update->where(['customers.status' => 'inactive']);
```

Produces:

```sql
UPDATE orders
INNER JOIN customers ON orders.customerId = customers.id
SET status = ?
WHERE customers.status = ?
```

Note: JOIN support in UPDATE statements varies by database platform. MySQL and
PostgreSQL support this syntax, while some other databases may not.

## Expression and Literal Advanced Usage

### Distinguishing between Expression and Literal

Use `Literal` for static SQL fragments without parameters:

```php
$literal = new Literal('NOW()');
$literal = new Literal('CURRENT_TIMESTAMP');
$literal = new Literal('COUNT(*)');
```

Use `Expression` when parameters are needed:

```php
$expression = new Expression('DATE_ADD(NOW(), INTERVAL ? DAY)', [7]);
$expression = new Expression('CONCAT(?, ?)', ['Hello', 'World']);
```

### Mixed parameter types in expressions

```php
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

```sql
CASE WHEN COUNT(*) > 10 THEN HIGH ELSE LOW END
```

### Using database-specific functions

```php
$select->where(new Predicate\Expression(
    'FIND_IN_SET(?, ?)',
    [
        Argument::value('admin'),
        Argument::identifier('roles'),
    ]
));
```

## TableIdentifier

The `TableIdentifier` class provides a type-safe way to reference tables,
especially when working with schemas or databases.

```php
use PhpDb\Sql\TableIdentifier;

$table = new TableIdentifier('users', 'production');

$tableName = $table->getTable();
$schemaName = $table->getSchema();

[$table, $schema] = $table->getTableAndSchema();
```

Usage in SQL objects:

```php
$select = new Select(new TableIdentifier('orders', 'ecommerce'));

$select->join(
    new TableIdentifier('customers', 'crm'),
    'orders.customerId = customers.id'
);
```

Produces:

```sql
SELECT * FROM "ecommerce"."orders"
INNER JOIN "crm"."customers" ON orders.customerId = customers.id
```

With aliases:

```php
$select->from(['o' => new TableIdentifier('orders', 'sales')])
    ->join(
        ['c' => new TableIdentifier('customers', 'crm')],
        'o.customerId = c.id'
    );
```

## Working with the Sql Factory Class

The `Sql` class serves as a factory for creating SQL statement objects and provides methods for preparing and building SQL strings.

```php
use PhpDb\Sql\Sql;

$sql = new Sql($adapter);
$sql = new Sql($adapter, 'defaultTable');
```

### Factory Methods

```php
$select = $sql->select();
$select = $sql->select('users');

$insert = $sql->insert();
$insert = $sql->insert('users');

$update = $sql->update();
$update = $sql->update('users');

$delete = $sql->delete();
$delete = $sql->delete('users');
```

When a default table is set on the Sql instance, it will be used for all created statements unless overridden:

```php
$sql = new Sql($adapter, 'users');
$select = $sql->select();
$insert = $sql->insert();
```

### Preparing Statements

The recommended approach for executing queries is to prepare them first:

```php
$select = $sql->select('users')->where(['status' => 'active']);
$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();
```

This approach:
- Uses parameter binding for security against SQL injection
- Allows the database to cache query plans
- Is the preferred method for production code

### Building SQL Strings

For debugging or special cases, you can build the SQL string directly:

```php
$select = $sql->select('users')->where(['id' => 5]);
$sqlString = $sql->buildSqlString($select);
```

Note: Direct string building bypasses parameter binding. Use with caution and never with user input.

### Accessing the Platform

```php
$platform = $sql->getSqlPlatform();
```

The platform object handles database-specific SQL generation and can be used for custom query building.

## Common Patterns and Best Practices

### Handling Column Name Conflicts in JOINs

When joining tables with columns that have the same name, explicitly specify column aliases to avoid ambiguity:

```php
$select->from(['u' => 'users'])
    ->columns([
        'userId' => 'id',
        'userName' => 'name',
        'userEmail' => 'email',
    ])
    ->join(
        ['o' => 'orders'],
        'u.id = o.userId',
        [
            'orderId' => 'id',
            'orderDate' => 'createdAt',
            'orderAmount' => 'amount',
        ]
    );
```

This prevents confusion and ensures all columns are accessible in the result set.

### Working with NULL Values

NULL requires special handling in SQL. Use the appropriate predicates:

```php
$select->where(['deletedAt' => null]);

$select->where->isNull('deletedAt')
    ->or
    ->lessThan('deletedAt', new Expression('NOW()'));
```

In UPDATE statements:

```php
$update->set(['optionalField' => null]);
```

In comparisons, remember that `column = NULL` does not work in SQL; you must use `IS NULL`:

```php
$select->where->nest()
    ->isNull('field')
    ->or
    ->equalTo('field', '')
->unnest();
```

### Dynamic Query Building

Build queries dynamically based on conditions:

```php
$select = $sql->select('products');

if ($categoryId) {
    $select->where(['categoryId' => $categoryId]);
}

if ($minPrice) {
    $select->where->greaterThanOrEqualTo('price', $minPrice);
}

if ($maxPrice) {
    $select->where->lessThanOrEqualTo('price', $maxPrice);
}

if ($searchTerm) {
    $select->where->nest()
        ->like('name', '%' . $searchTerm . '%')
        ->or
        ->like('description', '%' . $searchTerm . '%')
    ->unnest();
}

if ($sortBy) {
    $select->order($sortBy . ' ' . ($sortDirection ?? 'ASC'));
}

if ($limit) {
    $select->limit($limit);
    if ($offset) {
        $select->offset($offset);
    }
}
```

### Reusing Query Components

Create reusable query components for common patterns:

```php
function applyActiveFilter(Select $select): Select
{
    return $select->where([
        'status' => 'active',
        'deletedAt' => null,
    ]);
}

function applyPagination(Select $select, int $page, int $perPage): Select
{
    return $select
        ->limit($perPage)
        ->offset(($page - 1) * $perPage);
}

$select = $sql->select('users');
applyActiveFilter($select);
applyPagination($select, 2, 25);
```

## Troubleshooting and Common Issues

### Empty WHERE Protection Errors

If you encounter errors about empty WHERE clauses:

```php
$update = $sql->update('users');
$update->set(['status' => 'inactive']);
```

Always include a WHERE clause for UPDATE and DELETE:

```php
$update->where(['id' => 123]);
```

To intentionally update all rows (use with extreme caution):

```php
$state = $update->getRawState();
```

### Parameter Count Mismatch

When using Expression with placeholders:

```php
$expression = new Expression('CONCAT(?, ?, ?)', ['a', 'b']);
```

Ensure the number of `?` placeholders matches the number of parameters provided, or you will receive a RuntimeException.

### Quote Character Issues

Different databases use different quote characters. Let the platform handle quoting:

```php
$select->from('users');
```

Avoid manually quoting identifiers:

```php
$select->from('"users"');
```

### Type Confusion in Predicates

When comparing two identifiers, specify both types:

```php
$where->equalTo(
    'table1.columnA',
    'table2.columnB',
    Predicate\Predicate::TYPE_IDENTIFIER,
    Predicate\Predicate::TYPE_IDENTIFIER
);
```

Or use the Argument class:

```php
$where->equalTo(
    Argument::identifier('table1.columnA'),
    Argument::identifier('table2.columnB')
);
```

## Performance Considerations

### Use Prepared Statements

Always use `prepareStatementForSqlObject()` instead of `buildSqlString()` for user input:

```php
$select->where(['username' => $userInput]);
$statement = $sql->prepareStatementForSqlObject($select);
```

This provides:
- Protection against SQL injection
- Better performance through query plan caching
- Proper type handling for parameters

### Limit Result Sets

Always use `limit()` for queries that may return large result sets:

```php
$select->limit(100);
```

For pagination, combine with `offset()`:

```php
$select->limit(25)->offset(50);
```

### Select Only Required Columns

Instead of selecting all columns:

```php
$select->from('users');
```

Specify only the columns you need:

```php
$select->from('users')->columns(['id', 'username', 'email']);
```

This reduces memory usage and network transfer.

### Avoid N+1 Query Problems

Use JOINs instead of multiple queries:

```php
$select->from('orders')
    ->join('customers', 'orders.customerId = customers.id', ['customerName' => 'name'])
    ->join('products', 'orders.productId = products.id', ['productName' => 'name']);
```

### Index-Friendly Queries

Structure WHERE clauses to use database indexes:

```php
$select->where->equalTo('indexedColumn', $value)
    ->greaterThan('date', '2024-01-01');
```

Avoid functions on indexed columns in WHERE:

```php
$select->where(new Predicate\Expression('YEAR(createdAt) = ?', [2024]));
```

Instead, use ranges:

```php
$select->where->between('createdAt', '2024-01-01', '2024-12-31');
```

## Complete Examples

### Complex reporting query with aggregation

```php
$select = $sql->select('orders')
    ->columns([
        'customerId',
        'orderYear' => new Expression('YEAR(createdAt)'),
        'orderCount' => new Expression('COUNT(*)'),
        'totalRevenue' => new Expression('SUM(amount)'),
        'avgOrderValue' => new Expression('AVG(amount)'),
    ])
    ->join(
        'customers',
        'orders.customerId = customers.id',
        ['customerName' => 'name', 'customerTier' => 'tier'],
        Select::JOIN_LEFT
    )
    ->where(function ($where) {
        $where->nest()
            ->equalTo('orders.status', 'completed')
            ->or
            ->equalTo('orders.status', 'shipped')
        ->unnest();
        $where->between('orders.createdAt', '2024-01-01', '2024-12-31');
    })
    ->group(['customerId', new Expression('YEAR(createdAt)')])
    ->having(function ($having) {
        $having->greaterThan(new Expression('SUM(amount)'), 10000);
    })
    ->order(['totalRevenue DESC', 'orderYear DESC'])
    ->limit(100);

$statement = $sql->prepareStatementForSqlObject($select);
$results = $statement->execute();
```

### Data migration with INSERT SELECT

```php
$select = $sql->select('importedUsers')
    ->columns(['username', 'email', 'firstName', 'lastName'])
    ->where(['validated' => true])
    ->where->isNotNull('email');

$insert = $sql->insert('users');
$insert->columns(['username', 'email', 'firstName', 'lastName']);
$insert->select($select);

$statement = $sql->prepareStatementForSqlObject($insert);
$statement->execute();
```

### Combining multiple result sets

```php
$activeUsers = $sql->select('users')
    ->columns(['id', 'name', 'email', 'status' => new Literal('"active"')])
    ->where(['status' => 'active']);

$pendingUsers = $sql->select('userRegistrations')
    ->columns(['id', 'name', 'email', 'status' => new Literal('"pending"')])
    ->where(['verified' => false]);

$suspendedUsers = $sql->select('users')
    ->columns(['id', 'name', 'email', 'status' => new Literal('"suspended"')])
    ->where(['suspended' => true]);

$combine = new Combine();
$combine->union($activeUsers);
$combine->union($pendingUsers);
$combine->union($suspendedUsers);
$combine->alignColumns();

$statement = $sql->prepareStatementForSqlObject($combine);
$results = $statement->execute();
```
