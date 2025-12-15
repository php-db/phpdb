<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Clause\Group;
use PhpDb\Sql\Clause\Having;
use PhpDb\Sql\Clause\Join;
use PhpDb\Sql\Clause\Limit;
use PhpDb\Sql\Clause\Offset;
use PhpDb\Sql\Clause\Order;
use PhpDb\Sql\Clause\SelectExpression;
use PhpDb\Sql\Clause\Where;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function count;
use function gettype;
use function is_array;
use function is_numeric;
use function is_string;
use function key;
use function sprintf;
use function strtolower;

/**
 * @property Where  $where
 * @property Having $having
 * @property Join   $joins
 */
class Select extends AbstractPreparableSql
{
    /**#@+
     * Constant
     *
     * @const
     */
    public const SELECT = 'select';

    public const QUANTIFIER = 'quantifier';

    public const COLUMNS = 'columns';

    public const TABLE = 'table';

    public const JOINS = 'joins';

    public const WHERE = 'where';

    public const GROUP = 'group';

    public const HAVING = 'having';

    public const ORDER = 'order';

    public const LIMIT = 'limit';

    public const OFFSET = 'offset';

    public const QUANTIFIER_DISTINCT = 'DISTINCT';

    public const QUANTIFIER_ALL = 'ALL';

    public const JOIN_INNER = Join::JOIN_INNER;

    public const JOIN_OUTER = Join::JOIN_OUTER;

    public const JOIN_FULL_OUTER = Join::JOIN_FULL_OUTER;

    public const JOIN_LEFT = Join::JOIN_LEFT;

    public const JOIN_RIGHT = Join::JOIN_RIGHT;

    public const JOIN_RIGHT_OUTER = Join::JOIN_RIGHT_OUTER;

    public const JOIN_LEFT_OUTER = Join::JOIN_LEFT_OUTER;

    public const SQL_STAR = '*';

    public const ORDER_ASCENDING = 'ASC';

    public const ORDER_DESCENDING = 'DESC';

    public const COMBINE = 'combine';

    public const COMBINE_UNION = 'union';

    public const COMBINE_EXCEPT = 'except';

    public const COMBINE_INTERSECT = 'intersect';

    protected ?TableIdentifier $table = null;

    /** Subselect in FROM clause (when from() receives a Select object) */
    protected ?self $subselect = null;

    /** Alias for subselect in FROM clause */
    protected ?string $subselectAlias = null;

    protected string|ExpressionInterface|null $quantifier = null;

    protected ?SelectExpression $columns = null;

    protected ?Join $joins = null;

    protected ?Where $where = null;

    protected ?Order $order = null;

    protected ?Group $group = null;

    protected ?Having $having = null;

    protected ?Limit $limit = null;

    protected ?Offset $offset = null;

    protected array $combine = [];

    /**
     * Constructor
     */
    public function __construct(array|string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->table = TableIdentifier::from($table, null, true);
        }
    }

    private function getWhere(): Where
    {
        return $this->where ??= new Where();
    }

    private function getJoins(): Join
    {
        return $this->joins ??= new Join();
    }

    private function getHaving(): Having
    {
        return $this->having ??= new Having();
    }

    /**
     * Create from clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function from(array|string|TableIdentifier|self $table): static
    {
        if ($this->table?->isReadOnly()) {
            throw new Exception\InvalidArgumentException(
                'Since this object was created with a table and/or schema in the constructor, it is read only.'
            );
        }

        // Handle subselect passed directly
        if ($table instanceof self) {
            $this->subselect = $table;
            $this->subselectAlias = null;
            $this->table = null;
            return $this;
        }

        // Handle array with subselect: ['alias' => Select]
        if (is_array($table)) {
            if (! is_string(key($table)) || count($table) !== 1) {
                throw new Exception\InvalidArgumentException(
                    'from() expects $table as an array is a single element associative array'
                );
            }

            $alias = (string) key($table);
            $value = current($table);

            if ($value instanceof self) {
                $this->subselect = $value;
                $this->subselectAlias = $alias;
                $this->table = null;
                return $this;
            }
        }

        $this->table = TableIdentifier::from($table);
        $this->subselect = null;
        $this->subselectAlias = null;

        return $this;
    }

    /**
     * @param string|Expression $quantifier DISTINCT|ALL
     */
    public function quantifier(ExpressionInterface|string $quantifier): static
    {
        $this->quantifier = $quantifier;

        return $this;
    }

    /**
     * Specify columns from which to select
     * Possible valid states:
     *   array(*)
     *   array(value, ...)
     *     value can be strings or Expression objects
     *   array(string => value, ...)
     *     key string will be use as alias,
     *     value can be string or Expression objects
     */
    public function columns(array $columns, bool $prefixColumnsWithTable = true): static
    {
        $this->columns = new SelectExpression($columns, $prefixColumnsWithTable);

        return $this;
    }

    private function getColumns(): SelectExpression
    {
        return $this->columns ??= new SelectExpression();
    }

    /**
     * Create join clause
     *
     * @param string $type one of the JOIN_* constants
     * @throws Exception\InvalidArgumentException
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = self::SQL_STAR,
        string $type = self::JOIN_INNER
    ): static {
        $this->getJoins()->join($name, $on, $columns, $type);

        return $this;
    }

    /**
     * Create where clause
     *
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     * @throws Exception\InvalidArgumentException
     */
    public function where(
        PredicateInterface|array|string|Closure $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): self {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            $this->getWhere()->addPredicates($predicate, $combination);
        }

        return $this;
    }

    public function group(mixed $group): static
    {
        ($this->group ??= new Group())->add($group);

        return $this;
    }

    /**
     * Create having clause
     *
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     */
    public function having(
        Having|PredicateInterface|array|Closure|string $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        if ($predicate instanceof Having) {
            $this->having = $predicate;
        } else {
            $this->getHaving()->addPredicates($predicate, $combination);
        }

        return $this;
    }

    public function order(ExpressionInterface|array|string $order): static
    {
        ($this->order ??= new Order())->add($order);

        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function limit(int|string $limit): static
    {
        if (! is_numeric($limit)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects parameter to be numeric, "%s" given',
                __METHOD__,
                gettype($limit)
            ));
        }

        $this->limit = new Limit($limit);

        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function offset(int|string $offset): static
    {
        if (! is_numeric($offset)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects parameter to be numeric, "%s" given',
                __METHOD__,
                gettype($offset)
            ));
        }

        $this->offset = new Offset($offset);

        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function combine(Select $select, string $type = self::COMBINE_UNION, string $modifier = ''): static
    {
        if ($this->combine !== []) {
            throw new Exception\InvalidArgumentException(
                'This Select object is already combined and cannot be combined with multiple Selects objects'
            );
        }

        $this->combine = [
            'select'   => $select,
            'type'     => $type,
            'modifier' => $modifier,
        ];

        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function reset(string $part): static
    {
        switch ($part) {
            case self::TABLE:
                if ($this->table?->isReadOnly()) {
                    throw new Exception\InvalidArgumentException(
                        'Since this object was created with a table and/or schema in the constructor, it is read only.'
                    );
                }

                $this->table = null;
                $this->subselect = null;
                $this->subselectAlias = null;
                break;
            case self::QUANTIFIER:
                $this->quantifier = null;
                break;
            case self::COLUMNS:
                $this->columns = null;
                break;
            case self::JOINS:
                $this->joins = null;
                break;
            case self::WHERE:
                $this->where = null;
                break;
            case self::GROUP:
                $this->group = null;
                break;
            case self::HAVING:
                $this->having = null;
                break;
            case self::LIMIT:
                $this->limit = null;
                break;
            case self::OFFSET:
                $this->offset = null;
                break;
            case self::ORDER:
                $this->order = null;
                break;
            case self::COMBINE:
                $this->combine = [];
                break;
        }

        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            self::TABLE      => $this->table,
            self::QUANTIFIER => $this->quantifier,
            self::COLUMNS    => $this->columns?->columns ?? [SelectExpression::SQL_STAR],
            self::JOINS      => $this->joins,
            self::WHERE      => $this->where,
            self::ORDER      => $this->order,
            self::GROUP      => $this->group,
            self::HAVING     => $this->having,
            self::LIMIT      => $this->limit,
            self::OFFSET     => $this->offset,
            self::COMBINE    => $this->combine,
        ];

        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Returns whether the table is read only or not.
     */
    public function isTableReadOnly(): bool
    {
        return $this->table?->isReadOnly() ?? false;
    }

    /**
     * Optimized buildSqlString using direct concatenation with coalescing
     */
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $builder = new PreparableSqlBuilder($platform, $driver, $parameterContainer);

        $sql = $this->buildSelectPart($builder)
            . ($this->joins?->prepareSqlString($builder) ?? '')
            . ($this->where?->prepareSqlString($builder) ?? '')
            . ($this->group?->prepareSqlString($builder) ?? '')
            . ($this->having?->prepareSqlString($builder) ?? '');

        // Handle UNION/INTERSECT/EXCEPT (combine)
        if ($this->combine !== []) {
            /** @var Select $combineSelect */
            $combineSelect = $this->combine['select'];
            $combineType = strtoupper($this->combine['type']);
            $combineModifier = $this->combine['modifier'] !== '' ? ' ' . strtoupper($this->combine['modifier']) : '';

            $sql = '( ' . $sql . ' ) ' . $combineType . $combineModifier
                . ' ( ' . $builder->processSubSelect($combineSelect) . ' )';
        }

        return $sql
            . ($this->order?->prepareSqlString($builder) ?? '')
            . ($this->limit?->prepareSqlString($builder) ?? '')
            . ($this->offset?->prepareSqlString($builder) ?? '');
    }

    /**
     * Build the SELECT ... FROM part of the query - optimized for speed
     */
    protected function buildSelectPart(PreparableSqlBuilder $builder): string
    {
        $quantifierPart = '';
        if ($this->quantifier !== null) {
            $quantifierPart = $this->quantifier instanceof ExpressionInterface
                ? $builder->processExpression($this->quantifier) . ' '
                : $this->quantifier . ' ';
        }

        // For column prefixing, use table OR subselect alias
        $prefixTable = $this->table;
        if ($prefixTable === null && $this->subselectAlias !== null) {
            $prefixTable = new TableIdentifier($this->subselectAlias);
        }

        $columnsPart = $this->getColumns()->prepareSqlString($builder, $prefixTable);
        $joinColumnsPart = $this->joins?->toColumnsSqlPart($builder) ?? '';

        // If main columns are empty but join columns exist, remove leading comma from join columns
        if ($columnsPart === '' && $joinColumnsPart !== '') {
            $joinColumnsPart = substr($joinColumnsPart, 2); // Remove leading ", "
        }

        // Handle FROM clause - either table or subselect
        $fromPart = '';
        if ($this->subselect !== null) {
            $q = $builder->q;
            $fromPart = ' FROM (' . $builder->processSubSelect($this->subselect) . ')';
            if ($this->subselectAlias !== null) {
                $fromPart .= ' AS ' . $q . $this->subselectAlias . $q;
            }
        } elseif ($this->table !== null) {
            $fromPart = $this->table->toFromSqlPart($builder);
        }

        return 'SELECT ' . $quantifierPart
            . $columnsPart
            . $joinColumnsPart
            . $fromPart;
    }

    /**
     * Variable overloading
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $name): Where|Join|Having
    {
        return match (strtolower($name)) {
            'where' => $this->getWhere(),
            'having' => $this->getHaving(),
            'joins' => $this->getJoins(),
            default => throw new Exception\InvalidArgumentException('Not a valid magic property for this object'),
        };
    }

    /**
     * __clone
     * Resets the where object each time the Select is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->columns !== null) {
            $this->columns = clone $this->columns;
        }
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
        if ($this->joins !== null) {
            $this->joins = clone $this->joins;
        }
        if ($this->having !== null) {
            $this->having = clone $this->having;
        }
        if ($this->group !== null) {
            $this->group = clone $this->group;
        }
        if ($this->order !== null) {
            $this->order = clone $this->order;
        }
        if ($this->limit !== null) {
            $this->limit = clone $this->limit;
        }
        if ($this->offset !== null) {
            $this->offset = clone $this->offset;
        }
    }
}
