# Where and Having

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

## Using where() and having()

`PhpDb\Sql\Select` provides bit of flexibility as it regards to what kind of
parameters are acceptable when calling `where()` or `having()`. The method
signature is listed as:

### Method signature for where() and having()

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

### Using a callable with where()

```php
$select->where(function (Where $where) {
    $where->like('username', 'ralph%');
});
```

If you provide a *string*, this string will be used to create a
`PhpDb\Sql\Predicate\Expression` instance, and its contents will be applied
as-is, with no quoting:

### Using a string expression with where()

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

### Using an array of string expressions

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

### Using an associative array with mixed value types

```php
// SELECT "foo".* FROM "foo" WHERE "c1" IS NULL
//        AND "c2" IN (?, ?, ?) AND "c3" IS NOT NULL
$select->from('foo')->where([
    'c1' => null,
    'c2' => [1, 2, 3],
    new \PhpDb\Sql\Predicate\IsNotNull('c3'),
]);
```

As another example of complex queries with nested conditions e.g.

### SQL example with nested OR and AND conditions

```sql
SELECT * WHERE (column1 is null or column1 = 2) AND (column2 = 3)
```

you need to use the `nest()` and `unnest()` methods, as follows:

### Using nest() and unnest() for complex conditions

```php
$select->where->nest() // bracket opened
    ->isNull('column1')
    ->or
    ->equalTo('column1', '2')
    ->unnest();  // bracket closed
    ->equalTo('column2', '3');
```

## Predicate API

The `Where` and `Having` API is that of `Predicate` and `PredicateSet`:

### Predicate class API definition

```php
// Where & Having extend Predicate:
class Predicate extends PredicateSet
{
    // Magic properties for fluent chaining
    public Predicate $and;
    public Predicate $or;
    public Predicate $nest;
    public Predicate $unnest;

    public function nest() : Predicate;
    public function setUnnest(?Predicate $predicate = null) : void;
    public function unnest() : Predicate;
    public function equalTo(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ) : static;
    public function notEqualTo(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ) : static;
    public function lessThan(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ) : static;
    public function greaterThan(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ) : static;
    public function lessThanOrEqualTo(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ) : static;
    public function greaterThanOrEqualTo(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ) : static;
    public function like(
        null|float|int|string|ArgumentInterface $identifier,
        null|float|int|string|ArgumentInterface $like
    ) : static;
    public function notLike(
        null|float|int|string|ArgumentInterface $identifier,
        null|float|int|string|ArgumentInterface $notLike
    ) : static;
    public function literal(string $literal) : static;
    public function expression(
        string $expression,
        null|string|float|int|array|ArgumentInterface
            |ExpressionInterface $parameters = []
    ) : static;
    public function isNull(
        float|int|string|ArgumentInterface $identifier
    ) : static;
    public function isNotNull(
        float|int|string|ArgumentInterface $identifier
    ) : static;
    public function in(
        float|int|string|ArgumentInterface $identifier,
        array|ArgumentInterface $valueSet
    ) : static;
    public function notIn(
        float|int|string|ArgumentInterface $identifier,
        array|ArgumentInterface $valueSet
    ) : static;
    public function between(
        null|float|int|string|array|ArgumentInterface $identifier,
        null|float|int|string|array|ArgumentInterface $minValue,
        null|float|int|string|array|ArgumentInterface $maxValue
    ) : static;
    public function notBetween(
        null|float|int|string|array|ArgumentInterface $identifier,
        null|float|int|string|array|ArgumentInterface $minValue,
        null|float|int|string|array|ArgumentInterface $maxValue
    ) : static;
    public function predicate(PredicateInterface $predicate) : static;

    // Inherited From PredicateSet

    public function addPredicate(
        PredicateInterface $predicate,
        ?string $combination = null
    ) : static;
    public function addPredicates(
        PredicateInterface|Closure|string|array $predicates,
        string $combination = self::OP_AND
    ) : static;
    public function getPredicates() : array;
    public function orPredicate(
        PredicateInterface $predicate
    ) : static;
    public function andPredicate(
        PredicateInterface $predicate
    ) : static;
    public function getExpressionData() : ExpressionData;
    public function count() : int;
}
```

> **Note:** The `$leftType` and `$rightType` parameters have been removed
> from comparison methods. Type information is now encoded within
> `ArgumentInterface` implementations. Pass an `Argument\Identifier` for
> column names, `Argument\Value` for values, or `Argument\Literal` for raw
> SQL fragments directly to control how values are treated.

Each method in the API will produce a corresponding `Predicate` object of a
similarly named type, as described below.

## Comparison Predicates

### equalTo(), lessThan(), greaterThan(), lessThanOrEqualTo(), greaterThanOrEqualTo()

### Using equalTo() to create an Operator predicate

```php
$where->equalTo('id', 5);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Operator('id', Operator::OPERATOR_EQUAL_TO, 5)
);
```

Operators use the following API:

### Operator class API definition

```php
class Operator implements PredicateInterface
{
    final public const OPERATOR_EQUAL_TO                  = '=';
    final public const OP_EQ                              = '=';
    final public const OPERATOR_NOT_EQUAL_TO              = '!=';
    final public const OP_NE                              = '!=';
    final public const OPERATOR_LESS_THAN                 = '<';
    final public const OP_LT                              = '<';
    final public const OPERATOR_LESS_THAN_OR_EQUAL_TO     = '<=';
    final public const OP_LTE                             = '<=';
    final public const OPERATOR_GREATER_THAN              = '>';
    final public const OP_GT                              = '>';
    final public const OPERATOR_GREATER_THAN_OR_EQUAL_TO  = '>=';
    final public const OP_GTE                             = '>=';

    public function __construct(
        null|string|ArgumentInterface
            |ExpressionInterface|SqlInterface $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        null|bool|string|int|float|ArgumentInterface
            |ExpressionInterface|SqlInterface $right = null
    );
    public function setLeft(
        string|ArgumentInterface|ExpressionInterface|SqlInterface $left
    ) : static;
    public function getLeft() : ?ArgumentInterface;
    public function setOperator(string $operator) : static;
    public function getOperator() : string;
    public function setRight(
        null|bool|string|int|float|ArgumentInterface
            |ExpressionInterface|SqlInterface $right
    ) : static;
    public function getRight() : ?ArgumentInterface;
    public function getExpressionData() : ExpressionData;
}
```

> **Note:** The `setLeftType()`, `getLeftType()`, `setRightType()`, and
> `getRightType()` methods have been removed. Type information is now
> encoded within the `ArgumentInterface` implementations. Pass
> `Argument\Identifier`, `Argument\Value`, or `Argument\Literal` directly
> to `setLeft()` and `setRight()` to control how values are treated.

## Pattern Matching Predicates

### like($identifier, $like), notLike($identifier, $notLike)

### Using like() to create a Like predicate

```php
$where->like($identifier, $like):

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Like($identifier, $like)
);
```

The following is the `Like` API:

### Like class API definition

```php
class Like implements PredicateInterface
{
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|bool|float|int|string|ArgumentInterface $like = null
    );
    public function setIdentifier(string|ArgumentInterface $identifier) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setLike(
        bool|float|int|null|string|ArgumentInterface $like
    ) : static;
    public function getLike() : ?ArgumentInterface;
    public function setSpecification(string $specification) : static;
    public function getSpecification() : string;
    public function getExpressionData() : ExpressionData;
}
```

## Literal and Expression Predicates

### literal($literal)

### Using literal() to create a Literal predicate

```php
$where->literal($literal);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Literal($literal)
);
```

The following is the `Literal` API:

### Literal class API definition

```php
class Literal implements ExpressionInterface, PredicateInterface
{
    public function __construct(string $literal = '');
    public function setLiteral(string $literal) : self;
    public function getLiteral() : string;
    public function getExpressionData() : ExpressionData;
}
```

### expression($expression, $parameter)

### Using expression() to create an Expression predicate

```php
$where->expression($expression, $parameter);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Expression($expression, $parameter)
);
```

The following is the `Expression` API:

### Expression class API definition

```php
class Expression implements ExpressionInterface, PredicateInterface
{
    final public const PLACEHOLDER = '?';

    public function __construct(
        string $expression = '',
        null|bool|string|float|int|array|ArgumentInterface
            |ExpressionInterface $parameters = []
    );
    public function setExpression(string $expression) : self;
    public function getExpression() : string;
    public function setParameters(
        null|bool|string|float|int|array|ExpressionInterface
            |ArgumentInterface $parameters = []
    ) : self;
    public function getParameters() : array;
    public function getExpressionData() : ExpressionData;
}
```

Expression parameters can be supplied in multiple ways:

### Using Expression with various parameter types

```php
// Using Argument classes for explicit typing
$expression = new Expression(
    'CONCAT(?, ?, ?)',
    [
        new Argument\Identifier('column1'),
        new Argument\Value('-'),
        new Argument\Identifier('column2')
    ]
);

// Scalar values are auto-wrapped as Argument\Value
$expression = new Expression('column > ?', 5);

// Complex expression with mixed argument types
$select
    ->from('foo')
    ->columns([
        new Expression(
            '(COUNT(?) + ?) AS ?',
            [
                new Argument\Identifier('some_column'),
                new Argument\Value(5),
                new Argument\Identifier('bar'),
            ],
        ),
    ]);

// Produces SELECT (COUNT("some_column") + '5') AS "bar" FROM "foo"
```

## NULL Predicates

### isNull($identifier)

### Using isNull() to create an IsNull predicate

```php
$where->isNull($identifier);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\IsNull($identifier)
);
```

The following is the `IsNull` API:

### IsNull class API definition

```php
class IsNull implements PredicateInterface
{
    public function __construct(null|string|ArgumentInterface $identifier = null);
    public function setIdentifier(string|ArgumentInterface $identifier) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setSpecification(string $specification) : static;
    public function getSpecification() : string;
    public function getExpressionData() : ExpressionData;
}
```

### isNotNull($identifier)

### Using isNotNull() to create an IsNotNull predicate

```php
$where->isNotNull($identifier);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\IsNotNull($identifier)
);
```

The following is the `IsNotNull` API:

### IsNotNull class API definition

```php
class IsNotNull implements PredicateInterface
{
    public function __construct(null|string|ArgumentInterface $identifier = null);
    public function setIdentifier(string|ArgumentInterface $identifier) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setSpecification(string $specification) : static;
    public function getSpecification() : string;
    public function getExpressionData() : ExpressionData;
}
```

## Set Predicates

### in($identifier, $valueSet), notIn($identifier, $valueSet)

### Using in() to create an In predicate

```php
$where->in($identifier, $valueSet);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\In($identifier, $valueSet)
);
```

The following is the `In` API:

### In class API definition

```php
class In implements PredicateInterface
{
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|array|Select|ArgumentInterface $valueSet = null
    );
    public function setIdentifier(string|ArgumentInterface $identifier) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setValueSet(
        array|Select|ArgumentInterface $valueSet
    ) : static;
    public function getValueSet() : ?ArgumentInterface;
    public function getExpressionData() : ExpressionData;
}
```

## Range Predicates

### between() and notBetween()

### Using between() to create a Between predicate

```php
$where->between($identifier, $minValue, $maxValue);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Between($identifier, $minValue, $maxValue)
);
```

The following is the `Between` API:

### Between class API definition

```php
class Between implements PredicateInterface
{
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|int|float|string|ArgumentInterface $minValue = null,
        null|int|float|string|ArgumentInterface $maxValue = null
    );
    public function setIdentifier(
        string|ArgumentInterface $identifier
    ) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setMinValue(
        null|int|float|string|bool|ArgumentInterface $minValue
    ) : static;
    public function getMinValue() : ?ArgumentInterface;
    public function setMaxValue(
        null|int|float|string|bool|ArgumentInterface $maxValue
    ) : static;
    public function getMaxValue() : ?ArgumentInterface;
    public function setSpecification(string $specification) : static;
    public function getSpecification() : string;
    public function getExpressionData() : ExpressionData;
}
```

As an example with different value types:

### Using between() with different value types

```php
$where->between('age', 18, 65);
$where->notBetween('price', 100, 500);
$where->between('createdAt', '2024-01-01', '2024-12-31');
```

Produces:

### SQL output for between() examples

```sql
WHERE age BETWEEN 18 AND 65 AND price NOT BETWEEN 100 AND 500 AND createdAt BETWEEN '2024-01-01' AND '2024-12-31'
```

Expressions can also be used:

### Using between() with an Expression

```php
$where->between(new Expression('YEAR(createdAt)'), 2020, 2024);
```

Produces:

### SQL output for between() with Expression

```sql
WHERE YEAR(createdAt) BETWEEN 2020 AND 2024
```

## Advanced Predicate Usage

### Magic properties for fluent chaining

The Predicate class provides magic properties that enable fluent method chaining
for combining predicates. These properties (`and`, `or`, `AND`, `OR`, `nest`,
`unnest`, `NEST`, `UNNEST`) facilitate readable query construction.

### Using magic properties for fluent chaining

```php
$select->where
    ->equalTo('status', 'active')
    ->and
    ->greaterThan('age', 18)
    ->or
    ->equalTo('role', 'admin');
```

Produces:

### SQL output for fluent chaining example

```sql
WHERE status = 'active' AND age > 18 OR role = 'admin'
```

The properties are case-insensitive for convenience:

### Case-insensitive magic property usage

```php
$where->and->equalTo('a', 1);
$where->AND->equalTo('b', 2');
```

### Deep nesting of predicates

Complex nested conditions can be created using `nest()` and `unnest()`:

### Creating deeply nested predicate conditions

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

### SQL output for deeply nested predicates

```sql
WHERE ((a = 1 OR b = 2) AND (c = 3 OR d = 4))
```

### addPredicates() intelligent handling

The `addPredicates()` method from `PredicateSet` provides intelligent handling of
various input types, automatically creating appropriate predicate objects based on
the input.

### Using addPredicates() with mixed input types

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

### Using addPredicates() with OR combination

```php
$where->addPredicates([
    'role' => 'admin',
    'status' => 'active',
], PredicateSet::OP_OR);
```

Produces:

### SQL output for OR combination

```sql
WHERE role = 'admin' OR status = 'active'
```

### Using LIKE and NOT LIKE patterns

The `like()` and `notLike()` methods support SQL wildcard patterns:

### Using like() and notLike() with wildcard patterns

```php
$where->like('name', 'John%');
$where->like('email', '%@gmail.com');
$where->like('description', '%keyword%');
$where->notLike('email', '%@spam.com');
```

Multiple LIKE conditions:

### Combining multiple LIKE conditions with OR

```php
$where->like('name', 'A%')
    ->or
    ->like('name', 'B%');
```

Produces:

### SQL output for multiple LIKE conditions

```sql
WHERE name LIKE 'A%' OR name LIKE 'B%'
```

### Using HAVING with aggregate functions

While `where()` filters rows before grouping, `having()` filters groups after
aggregation. The HAVING clause is used with GROUP BY and aggregate functions.

### Using HAVING to filter aggregate results

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

### SQL output for HAVING with aggregate functions

```sql
SELECT customerId, COUNT(*) AS orderCount, SUM(amount) AS totalAmount
FROM orders
WHERE amount > 0
GROUP BY customerId
HAVING COUNT(*) > 10 AND SUM(amount) > 1000
```

Using closures with HAVING:

### Using a closure with HAVING for complex conditions

```php
$select->having(function ($having) {
    $having->greaterThan(new Expression('AVG(rating)'), 4.5)
        ->or
        ->greaterThan(new Expression('COUNT(reviews)'), 100);
});
```

Produces:

### SQL output for HAVING with closure

```sql
HAVING AVG(rating) > 4.5 OR COUNT(reviews) > 100
```

## Subqueries in WHERE Clauses

Subqueries can be used in various contexts within SQL statements, including WHERE
clauses, FROM clauses, and SELECT columns.

### Subqueries in WHERE IN clauses

### Using a subquery in a WHERE IN clause

```php
$subselect = $sql->select('orders')
    ->columns(['customerId'])
    ->where(['status' => 'completed']);

$select = $sql->select('customers')
    ->where->in('id', $subselect);
```

Produces:

### SQL output for subquery in WHERE IN

```sql
SELECT customers.* FROM customers
WHERE id IN (SELECT customerId FROM orders WHERE status = 'completed')
```

### Subqueries in FROM clauses

### Using a subquery in a FROM clause

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

### SQL output for subquery in FROM clause

```sql
SELECT orderTotals.* FROM
(SELECT customerId, SUM(amount) AS total FROM orders GROUP BY customerId) AS orderTotals
WHERE orderTotals.total > 1000
```

### Scalar subqueries in SELECT columns

### Using a scalar subquery in SELECT columns

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

### SQL output for scalar subquery in SELECT

```sql
SELECT id, name,
(SELECT COUNT(*) FROM orders WHERE orders.customerId = customers.id) AS orderCount
FROM customers
```

### Subqueries with comparison operators

### Using a subquery with a comparison operator

```php
$subselect = $sql->select('orders')
    ->columns([new Expression('AVG(amount)')]);

$select = $sql->select('orders')
    ->where->greaterThan('amount', $subselect);
```

Produces:

### SQL output for subquery with comparison operator

```sql
SELECT orders.* FROM orders
WHERE amount > (SELECT AVG(amount) FROM orders)
```
