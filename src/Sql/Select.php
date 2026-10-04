<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function array_key_first;
use function count;
use function explode;
use function gettype;
use function is_array;
use function is_int;
use function is_numeric;
use function is_scalar;
use function is_string;
use function key;
use function method_exists;
use function preg_split;
use function str_contains;
use function strcasecmp;
use function stripos;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * @property Where $where
 * @property Having $having
 * @property Join $joins
 * @psalm-import-type Specification from AbstractSql
 * @psalm-import-type JoinName from Join
 * @psalm-type TableReference = string|TableIdentifier|array<string, string|TableIdentifier|Select>
 * @psalm-type ColumnList = array<array-key, string|ExpressionInterface>
 * @psalm-type Combination = array{select: Select, type: string, modifier: string}
 */
class Select extends AbstractPreparableSql
{
    /**#@+
     * Constant
     *
     * @const
     */
    final public const string SELECT = 'select';

    final public const string QUANTIFIER = 'quantifier';

    final public const string COLUMNS = 'columns';

    final public const string TABLE = 'table';

    final public const string JOINS = 'joins';

    final public const string WHERE = 'where';

    final public const string GROUP = 'group';

    final public const string HAVING = 'having';

    final public const string ORDER = 'order';

    final public const string LIMIT = 'limit';

    final public const string OFFSET = 'offset';

    final public const string QUANTIFIER_DISTINCT = 'DISTINCT';

    final public const string QUANTIFIER_ALL = 'ALL';

    final public const string JOIN_INNER = Join::JOIN_INNER;

    final public const string JOIN_OUTER = Join::JOIN_OUTER;

    final public const string JOIN_FULL_OUTER = Join::JOIN_FULL_OUTER;

    final public const string JOIN_LEFT = Join::JOIN_LEFT;

    final public const string JOIN_RIGHT = Join::JOIN_RIGHT;

    final public const string JOIN_RIGHT_OUTER = Join::JOIN_RIGHT_OUTER;

    final public const string JOIN_LEFT_OUTER = Join::JOIN_LEFT_OUTER;

    final public const string SQL_STAR = '*';

    final public const string ORDER_ASCENDING = 'ASC';

    final public const string ORDER_DESCENDING = 'DESC';

    final public const string COMBINE = 'combine';

    final public const string COMBINE_UNION = 'union';

    final public const string COMBINE_EXCEPT = 'except';

    final public const string COMBINE_INTERSECT = 'intersect';

    /** @var array<string, Specification> */
    protected array $specifications = [
        'statementStart' => '%1$s',
        self::SELECT     => [
            'SELECT %1$s FROM %2$s'      => [
                [1 => '%1$s', 2 => '%1$s AS %2$s', 'combinedby' => ', '],
                null,
            ],
            'SELECT %1$s %2$s FROM %3$s' => [
                null,
                [1 => '%1$s', 2 => '%1$s AS %2$s', 'combinedby' => ', '],
                null,
            ],
            'SELECT %1$s'                => [
                [1 => '%1$s', 2 => '%1$s AS %2$s', 'combinedby' => ', '],
            ],
        ],
        self::JOINS      => [
            '%1$s' => [
                [3 => '%1$s JOIN %2$s ON %3$s', 'combinedby' => ' '],
            ],
        ],
        self::WHERE      => 'WHERE %1$s',
        self::GROUP      => [
            'GROUP BY %1$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
            ],
        ],
        self::HAVING     => 'HAVING %1$s',
        self::ORDER      => [
            'ORDER BY %1$s' => [
                [1 => '%1$s', 2 => '%1$s %2$s', 'combinedby' => ', '],
            ],
        ],
        self::LIMIT      => 'LIMIT %1$s',
        self::OFFSET     => 'OFFSET %1$s',
        'statementEnd'   => '%1$s',
        self::COMBINE    => '%1$s ( %2$s )',
    ];

    protected bool $tableReadOnly = false;

    protected bool $prefixColumnsWithTable = true;

    /** @var TableReference|null */
    protected string|array|TableIdentifier|null $table = null;

    protected string|ExpressionInterface|null $quantifier = null;

    /** @var ColumnList */
    protected array $columns = [self::SQL_STAR];

    protected ?Join $joins = null;

    protected ?Where $where = null;

    /** @var ColumnList */
    protected array $order = [];

    /** @var list<string|ExpressionInterface>|null */
    protected ?array $group = null;

    protected ?Having $having = null;

    protected string|int|null $limit = null;

    protected string|int|null $offset = null;

    /** @var Combination|array{} */
    protected array $combine = [];

    /**
     * Constructor
     *
     * @param TableReference|null $table
     */
    public function __construct(array|string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->from($table);
            $this->tableReadOnly = true;
        }
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
     *
     * @param ColumnList $columns
     */
    public function columns(array $columns, bool $prefixColumnsWithTable = true): static
    {
        $this->columns                = $columns;
        $this->prefixColumnsWithTable = $prefixColumnsWithTable;
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function combine(Select $select, string $type = self::COMBINE_UNION, string $modifier = ''): static
    {
        if ([] !== $this->combine) {
            throw Exception\InvalidArgumentException::forAlreadyCombined();
        }

        $this->combine = [
            'select'   => $select,
            'type'     => $type,
            'modifier' => $modifier,
        ];
        return $this;
    }

    /**
     * Create from clause
     *
     * @param TableReference $table
     * @throws Exception\InvalidArgumentException
     */
    public function from(array|string|TableIdentifier $table): static
    {
        if ($this->tableReadOnly) {
            throw Exception\InvalidArgumentException::forReadOnlyConstructorState();
        }

        if (is_array($table) && (! is_string(key($table)) || count($table) !== 1)) {
            throw Exception\InvalidArgumentException::forInvalidFromArray();
        }

        $this->table = $table;
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            self::TABLE      => $this->table,
            self::QUANTIFIER => $this->quantifier,
            self::COLUMNS    => $this->columns,
            self::JOINS      => $this->getJoins(),
            self::WHERE      => $this->getWhere(),
            self::ORDER      => $this->order,
            self::GROUP      => $this->group,
            self::HAVING     => $this->getHaving(),
            self::LIMIT      => $this->limit,
            self::OFFSET     => $this->offset,
            self::COMBINE    => $this->combine,
        ];
        return null !== $key && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * @param string|ExpressionInterface|list<string|ExpressionInterface> $group
     */
    public function group(mixed $group): static
    {
        if (! is_array($group)) {
            $this->group[] = $group;

            return $this;
        }

        foreach ($group as $o) {
            $this->group[] = $o;
        }

        return $this;
    }

    /**
     * Create having clause
     *
     * @param Having|PredicateInterface|array<array-key, mixed>|Closure|string $predicate
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     */
    public function having(
        Having|PredicateInterface|array|Closure|string $predicate,
        string $combination = Predicate\PredicateSet::OP_AND,
    ): static {
        if ($predicate instanceof Having) {
            $this->having = $predicate;

            return $this;
        }

        $this->getHaving()->addPredicates($predicate, $combination);

        return $this;
    }

    /**
     * Returns whether the table is read only or not.
     */
    public function isTableReadOnly(): bool
    {
        return $this->tableReadOnly;
    }

    /**
     * Create join clause
     *
     * @param JoinName                                            $name
     * @param string|array<array-key, string|ExpressionInterface> $columns
     * @param string                                              $type one of the JOIN_* constants
     * @throws Exception\InvalidArgumentException
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = self::SQL_STAR,
        string $type = self::JOIN_INNER,
    ): static {
        $this->getJoins()->join($name, $on, $columns, $type);

        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function limit(int|string $limit): static
    {
        if (! is_numeric($limit)) {
            throw Exception\InvalidArgumentException::forNonNumericParameter(__METHOD__, gettype($limit));
        }

        $this->limit = $limit;
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function offset(int|string $offset): static
    {
        if (! is_numeric($offset)) {
            throw Exception\InvalidArgumentException::forNonNumericParameter(__METHOD__, gettype($offset));
        }

        $this->offset = $offset;
        return $this;
    }

    /**
     * @param ExpressionInterface|ColumnList|string $order
     */
    public function order(ExpressionInterface|array|string $order): static
    {
        $order = match (true) {
            is_string($order) => str_contains($order, ',') ? preg_split('#,\s+#', $order) : (array) $order,
            is_array($order)  => $order,
            default           => [$order],
        };

        foreach ($order as $k => $v) {
            if (is_string($k)) {
                $this->order[$k] = $v;
                continue;
            }

            $this->order[] = $v;
        }

        return $this;
    }

    /**
     * @param string|Expression $quantifier DISTINCT|ALL
     * @throws Exception\InvalidArgumentException
     */
    public function quantifier(ExpressionInterface|string $quantifier): static
    {
        $this->quantifier = $quantifier;
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function reset(string $part): static
    {
        switch ($part) {
            case self::TABLE:
                if ($this->tableReadOnly) {
                    throw Exception\InvalidArgumentException::forReadOnlyConstructorState();
                }

                $this->table = null;
                break;
            case self::QUANTIFIER:
                $this->quantifier = null;
                break;
            case self::COLUMNS:
                $this->columns = [];
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
                $this->order = [];
                break;
            case self::COMBINE:
                $this->combine = [];
                break;
        }

        return $this;
    }

    /**
     * @param Specification $specification
     */
    public function setSpecification(string $index, array|string $specification): static
    {
        if (! method_exists($this, "process{$index}")) {
            throw Exception\InvalidArgumentException::forInvalidSpecificationName();
        }

        $this->specifications[$index] = $specification;
        return $this;
    }

    /**
     * Create where clause
     *
     * @param PredicateInterface|array<array-key, mixed>|string|Closure $predicate
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     * @throws Exception\InvalidArgumentException
     */
    public function where(
        PredicateInterface|array|string|Closure $predicate,
        string $combination = Predicate\PredicateSet::OP_AND,
    ): self {
        if ($predicate instanceof Where) {
            $this->where = $predicate;

            return $this;
        }

        $this->getWhere()->addPredicates($predicate, $combination);

        return $this;
    }

    /** @return array{0: string, 1: string}|null */
    protected function processCombine(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if ([] === $this->combine) {
            return null;
        }

        $type = $this->combine['modifier']
            ? "{$this->combine['type']} {$this->combine['modifier']}"
            : $this->combine['type'];

        return [
            strtoupper($type),
            $this->processSubSelect($this->combine['select'], $platform, $driver, $parameterContainer),
        ];
    }

    /** @return array{0: list<string>}|null */
    protected function processGroup(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->group) {
            return null;
        }

        $groups = [];
        foreach ($this->group as $column) {
            $groups[] = $this->resolveColumnValue(
                [
                    'column'       => $column,
                    'isIdentifier' => true,
                ],
                $platform,
                $driver,
                $parameterContainer,
                'group',
            );
        }

        return [$groups];
    }

    /** @return array{0: string}|null */
    protected function processHaving(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->having || $this->having->count() === 0) {
            return null;
        }

        return [
            $this->processExpression($this->having, $platform, $driver, $parameterContainer, 'having'),
        ];
    }

    /** @return array{0: array<int, list<string>>}|null */
    protected function processJoins(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        return $this->processJoin($this->joins, $platform, $driver, $parameterContainer);
    }

    /** @return array{0: string}|null */
    protected function processLimit(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->limit) {
            return null;
        }

        if ($parameterContainer instanceof ParameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet("{$paramPrefix}limit", $this->limit, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName("{$paramPrefix}limit")];
        }

        return [$platform->quoteValue($this->limit)];
    }

    /** @return array{0: string}|null */
    protected function processOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->offset) {
            return null;
        }

        if ($parameterContainer instanceof ParameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet("{$paramPrefix}offset", $this->offset, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName("{$paramPrefix}offset")];
        }

        return [$platform->quoteValue($this->offset)];
    }

    /** @return array{0: list<list<string>>}|null */
    protected function processOrder(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if ([] === $this->order) {
            return null;
        }

        $orders = [];
        foreach ($this->order as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $orders[] = [
                    $this->processExpression($v, $platform, $driver, $parameterContainer),
                ];
                continue;
            }

            if (is_int($k)) {
                [$k, $v] = str_contains($v, ' ')
                    ? explode(' ', $v, limit: 2)
                    : [$v, self::ORDER_ASCENDING];
            }

            $orders[] = [
                $platform->quoteIdentifierInFragment($k),
                strcasecmp(trim($v), self::ORDER_DESCENDING) === 0
                    ? self::ORDER_DESCENDING
                    : self::ORDER_ASCENDING,
            ];
        }

        return [$orders];
    }

    /**
     * Process the select part
     *
     * @return list<string|list<list<string>>>
     */
    protected function processSelect(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): array {
        $expr = 1;

        [$table, $fromTable] = $this->resolveTable($this->table, $platform, $driver, $parameterContainer);
        $columns = [];
        foreach ($this->columns as $columnIndexOrAs => $column) {
            if (self::SQL_STAR === $column) {
                $columns[] = ["{$fromTable}*"];
                continue;
            }

            $columnName = $this->resolveColumnValue(
                [
                    'column'       => $column,
                    'fromTable'    => $fromTable,
                    'isIdentifier' => true,
                ],
                $platform,
                $driver,
                $parameterContainer,
                is_string($columnIndexOrAs) ? $columnIndexOrAs : 'column',
            );
            $columnAs = match (true) {
                is_string($columnIndexOrAs) => $platform->quoteIdentifier($columnIndexOrAs),
                stripos($columnName, needle: ' as ') === false => is_string($column)
                    ? $platform->quoteIdentifier($column)
                    : 'Expression' . $expr++,
                default => null,
            };

            $columns[] = null === $columnAs ? [$columnName] : [$columnName, $columnAs];
        }

        foreach ($this->getJoins()->getJoins() as $join) {
            $joinName = is_array($join['name']) ? array_key_first($join['name']) : $join['name'];
            $joinName = parent::resolveTable($joinName, $platform, $driver, $parameterContainer);

            foreach ($join['columns'] as $jKey => $jColumn) {
                $jColumns   = [];
                $jFromTable = is_scalar($jColumn)
                    ? $joinName . $platform->getIdentifierSeparator()
                    : '';
                $jColumns[] = $this->resolveColumnValue(
                    [
                        'column'       => $jColumn,
                        'fromTable'    => $jFromTable,
                        'isIdentifier' => true,
                    ],
                    $platform,
                    $driver,
                    $parameterContainer,
                    is_string($jKey) ? $jKey : 'column',
                );
                if (is_string($jKey)) {
                    $jColumns[] = $platform->quoteIdentifier($jKey);
                }

                if (! is_string($jKey) && self::SQL_STAR !== $jColumn) {
                    $jColumns[] = $platform->quoteIdentifier($jColumn);
                }

                $columns[] = $jColumns;
            }
        }

        $quantifier = null;
        if ($this->quantifier) {
            $quantifier = $this->quantifier instanceof ExpressionInterface
                ? $this->processExpression($this->quantifier, $platform, $driver, $parameterContainer, 'quantifier')
                : $this->quantifier;
        }

        if (null === $table) {
            return [$columns];
        }

        if (null !== $quantifier) {
            return [$quantifier, $columns, $table];
        }

        return [$columns, $table];
    }

    /** @return string[]|null */
    protected function processStatementEnd(): ?array
    {
        if ([] !== $this->combine) {
            return [')'];
        }

        return null;
    }

    /** @return string[]|null */
    protected function processStatementStart(): ?array
    {
        if ([] !== $this->combine) {
            return ['('];
        }

        return null;
    }

    /** @return array{0: string}|null */
    protected function processWhere(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->where || $this->where->count() === 0) {
            return null;
        }

        return [
            $this->processExpression($this->where, $platform, $driver, $parameterContainer, 'where'),
        ];
    }

    /**
     * @param Select|TableReference|null $table
     * @return array{0: string|null, 1: string}
     */
    #[Override]
    protected function resolveTable(
        Select|string|array|TableIdentifier|null $table,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): array {
        $alias = null;

        if (is_array($table)) {
            $alias = array_key_first($table);
            $table = $table[$alias];
        }

        $table = parent::resolveTable($table, $platform, $driver, $parameterContainer);

        $fromTable = $table;
        if ($alias) {
            $fromTable = $platform->quoteIdentifier($alias);
            $table     = $this->renderTable($table, $fromTable);
        }

        $fromTable = $this->prefixColumnsWithTable && $fromTable
            ? $fromTable . $platform->getIdentifierSeparator()
            : '';

        return [
            $table,
            $fromTable,
        ];
    }

    private function getHaving(): Having
    {
        return $this->having ??= new Having();
    }

    private function getJoins(): Join
    {
        return $this->joins ??= new Join();
    }

    private function getWhere(): Where
    {
        return $this->where ??= new Where();
    }

    /**
     * __clone
     *
     * Resets the where object each time the Select is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        if (null !== $this->where) {
            $this->where = clone $this->where;
        }
        if (null !== $this->joins) {
            $this->joins = clone $this->joins;
        }
        if (null !== $this->having) {
            $this->having = clone $this->having;
        }
    }

    /**
     * Variable overloading
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $name): Where|Join|Having
    {
        return match (strtolower($name)) {
            'where'  => $this->getWhere(),
            'having' => $this->getHaving(),
            'joins'  => $this->getJoins(),
            default  => throw Exception\InvalidArgumentException::forInvalidMagicProperty(),
        };
    }
}
