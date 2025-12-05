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
     public function prepareStatement(
         Adapter $adapter,
         StatementInterface $statement
     ) : void;
}

interface SqlInterface
{
     public function getSqlString(PlatformInterface $adapterPlatform = null) : string;
}
```

Use these functions to produce either (a) a prepared statement, or (b) a string
to execute.

## SQL Arguments and Argument Types

`PhpDb\Sql` provides individual `Argument\<type>` types as well as an
`Argument` factory class and an `ArgumentType` enum for type-safe
specification of SQL values. This provides a modern, object-oriented
alternative to using raw values or the legacy type constants.

The `ArgumentType` enum defines six types, each backed by its corresponding class:

- `Identifier` - For column names, table names, and other identifiers that
  should be quoted
- `Identifiers` - For arrays of identifiers (e.g., multi-column IN predicates)
- `Value` - For values that should be parameterized or properly escaped
  (default)
- `Values` - For arrays of values (e.g., IN clauses)
- `Literal` - For literal SQL fragments that should not be quoted or escaped
- `Select` - For subqueries (Expression or SqlInterface objects)

All argument classes are `readonly` and implement `ArgumentInterface`:

```php
use PhpDb\Sql\Argument;

// Using the Argument factory class (recommended)
$valueArg = Argument::value(123);             // Value type
$identifierArg = Argument::identifier('id');  // Identifier type
$literalArg = Argument::literal('NOW()');     // Literal SQL
$valuesArg = Argument::values([1, 2, 3]);     // Multiple values
$identifiersArg = Argument::identifiers(['col1', 'col2']); // Multiple identifiers

// Direct instantiation is preferred
$arg = new Argument\Identifier('column_name');
$arg = new Argument\Value(123);
$arg = new Argument\Literal('NOW()');
$arg = new Argument\Values([1, 2, 3]);
```

The `Argument` classes are particularly useful when working with expressions
where you need to explicitly control how values are treated:

```php
use PhpDb\Sql\Argument;
use PhpDb\Sql\Expression;

// With Argument classes - explicit and type-safe
$expression = new Expression(
    'CONCAT(?, ?, ?)',
    [
        new Argument\Identifier('column1'),
        new Argument\Value('-'),
        new Argument\Identifier('column2')
    ]
);
```

Scalar values passed directly to `Expression` are automatically wrapped:

- Scalars become `Argument\Value`
- Arrays become `Argument\Values`
- `ExpressionInterface` instances become `Argument\Select`

> ### Literals
>
> `PhpDb\Sql` makes the distinction that literals will not have any parameters
> that need interpolating, while `Expression` objects *might* have parameters
> that need interpolating. In cases where there are parameters in an `Expression`,
> `PhpDb\Sql\AbstractSql` will do its best to identify placeholders when the
> `Expression` is processed during statement creation. In short, if you don't
> have parameters, use `Literal` objects`.

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
class Select extends AbstractPreparableSql implements SqlInterface, PreparableSqlInterface
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
    public function from(array|string|TableIdentifier $table) : static;
    public function quantifier(ExpressionInterface|string $quantifier) : static;
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
    public function order(ExpressionInterface|array|string $order) : static;
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
    'id = bar.id',      // expression to join on (will be quoted by platform),
    ['bar', 'baz'],     // (optional) list of columns, same as columns() above
    $select::JOIN_OUTER // (optional), one of inner, outer, left, right, etc.
);

$select
    ->from(['f' => 'foo'])     // base table
    ->join(
        ['b' => 'bar'],        // join table with alias
        'f.foo_id = b.foo_id'  // join expression
    );
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
// SELECT "foo".* FROM "foo" WHERE "c1" IS NULL
//        AND "c2" IN (?, ?, ?) AND "c3" IS NOT NULL
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
$select->limit(5);   // always takes an integer/numeric
$select->offset(10); // similarly takes an integer/numeric
```

## Insert

The Insert API:

```php
class Insert extends AbstractPreparableSql implements SqlInterface, PreparableSqlInterface
{
    final public const VALUES_MERGE = 'merge';
    final public const VALUES_SET   = 'set';

    public function __construct(string|TableIdentifier|null $table = null);
    public function into(TableIdentifier|string|array $table) : static;
    public function columns(array $columns) : static;
    public function values(
        array|Select $values,
        string $flag = self::VALUES_SET
    ) : static;
    public function select(Select $select) : static;
    public function getRawState(?string $key = null) : TableIdentifier|string|array;
}
```

As with `Select`, the table may be provided during instantiation or via the
`into()` method.

### columns() (Insert)

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

## Update

```php
class Update extends AbstractPreparableSql implements SqlInterface, PreparableSqlInterface
{
    final public const VALUES_MERGE = 'merge';
    final public const VALUES_SET   = 'set';

    public Where $where;

    public function __construct(string|TableIdentifier|null $table = null);
    public function table(TableIdentifier|string|array $table) : static;
    public function set(array $values, string|int $flag = self::VALUES_SET) : static;
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ) : static;
    public function join(
        array|string|TableIdentifier $name,
        string $on,
        string $type = Join::JOIN_INNER
    ) : static;
    public function getRawState(?string $key = null) : mixed;
}
```

### set()

```php
$update->set(['foo' => 'bar', 'baz' => 'bax']);
```

### where()

See the [section on Where and Having](#where-and-having).

### join() (Update)

```php
$update->join('bar', 'foo.id = bar.foo_id', Update::JOIN_LEFT);
```

## Delete

```php
class Delete extends AbstractPreparableSql implements SqlInterface, PreparableSqlInterface
{
    public Where $where;

    public function __construct(string|TableIdentifier|null $table = null);
    public function from(TableIdentifier|string|array $table) : static;
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ) : static;
    public function getRawState(?string $key = null) : mixed;
}
```

### where() (Delete)

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

The `Where` and `Having` API is that of `Predicate` and `PredicateSet`:

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

### equalTo(), lessThan(), greaterThan(), lessThanOrEqualTo(), greaterThanOrEqualTo()

```php
$where->equalTo('id', 5);

// The above is equivalent to:
$where->addPredicate(
    new Predicate\Operator('id', Operator::OPERATOR_EQUAL_TO, 5)
);
```

Operators use the following API:

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
    public function __construct(string $literal = '');
    public function setLiteral(string $literal) : self;
    public function getLiteral() : string;
    public function getExpressionData() : ExpressionData;
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

```php
// Using Argument classes (recommended)
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

// Legacy array format still supported
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
    public function __construct(null|string|ArgumentInterface $identifier = null);
    public function setIdentifier(string|ArgumentInterface $identifier) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setSpecification(string $specification) : static;
    public function getSpecification() : string;
    public function getExpressionData() : ExpressionData;
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
    public function __construct(null|string|ArgumentInterface $identifier = null);
    public function setIdentifier(string|ArgumentInterface $identifier) : static;
    public function getIdentifier() : ?ArgumentInterface;
    public function setSpecification(string $specification) : static;
    public function getSpecification() : string;
    public function getExpressionData() : ExpressionData;
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

### between() and notBetween()

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
