<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use Laminas\Stdlib\PriorityList;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function array_keys;
use function array_map;
use function explode;
use function implode;
use function is_numeric;
use function is_scalar;
use function is_string;
use function strtolower;
use function strval;

/**
 * @api
 *
 * @property ?Where $where
 * @psalm-import-type Specification from AbstractSql
 * @psalm-import-type ColumnValue from AbstractSql
 * @psalm-import-type PredicateValue from Predicate\PredicateSet
 * @psalm-import-type JoinName from Join
 */
class Update extends AbstractPreparableSql
{
    /**@#++
     * @const
     */
    public const string SPECIFICATION_UPDATE = 'update';

    final public const string SPECIFICATION_SET = 'set';

    final public const string SPECIFICATION_WHERE = 'where';

    final public const string SPECIFICATION_JOIN = 'joins';

    final public const string VALUES_MERGE = 'merge';

    final public const string VALUES_SET = 'set';

    /** @#-* */

    /** @var array<string, Specification> */
    protected array $specifications = [
        self::SPECIFICATION_UPDATE => 'UPDATE %1$s',
        self::SPECIFICATION_JOIN   => [
            '%1$s' => [
                [3 => '%1$s JOIN %2$s ON %3$s', 'combinedby' => ' '],
            ],
        ],
        self::SPECIFICATION_SET    => 'SET %1$s',
        self::SPECIFICATION_WHERE  => 'WHERE %1$s',
    ];

    /** @var TableIdentifier|string|array<string, string|TableIdentifier> */
    protected TableIdentifier|string|array $table = '';

    protected bool $emptyWhereProtection = true;

    /** @var PriorityList<string, ColumnValue>|null */
    protected ?PriorityList $set = null;

    protected ?Where $where = null;

    protected ?Join $joins = null;

    /**
     * Constructor
     *
     * @param string|TableIdentifier|array<string, string|TableIdentifier>|null $table An aliased table
     *     array is kept for TableGateway and rendered as the bare table
     */
    public function __construct(string|TableIdentifier|array|null $table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table,
            'set'                  => $this->getSet()->toArray(),
            'where'                => $this->getWhere(),
            'joins'                => $this->getJoins(),
        ];
        return null !== $key && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Create join clause
     *
     * @param JoinName $name
     * @throws Exception\InvalidArgumentException
     */
    public function join(array|string|TableIdentifier $name, string $on, string $type = Join::JOIN_INNER): static
    {
        $this->getJoins()->join($name, $on, [], $type);

        return $this;
    }

    /**
     * Set key/value pairs to update
     *
     * @param array<array-key, ColumnValue> $values Associative array of key values
     * @param string|int              $flag   One of the VALUES_* constants, or a numeric priority
     * @throws Exception\InvalidArgumentException
     */
    public function set(array $values, string|int $flag = self::VALUES_SET): static
    {
        $set = $this->getSet();
        if (self::VALUES_SET === $flag) {
            $set->clear();
        }

        $priority = is_numeric($flag) ? (int) $flag : 0;
        foreach ($values as $k => $v) {
            if (! is_string($k)) {
                throw Exception\InvalidArgumentException::forNonStringValueKey();
            }

            $set->insert($k, $v, $priority);
        }

        return $this;
    }

    /**
     * Specify table for statement
     *
     * @param TableIdentifier|string|array<string, string|TableIdentifier> $table
     */
    public function table(TableIdentifier|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Create where clause
     *
     * @param PredicateInterface|array<array-key, PredicateValue>|Closure|string|Where $predicate
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     * @throws Exception\InvalidArgumentException
     */
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND,
    ): static {
        if ($predicate instanceof Where) {
            $this->where = $predicate;

            return $this;
        }

        $this->getWhere()->addPredicates($predicate, $combination);

        return $this;
    }

    /**
     * @return string[][][]|null
     * @throws Exception\RuntimeException
     */
    protected function processJoins(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        return $this->processJoin($this->joins, $platform, $driver, $parameterContainer);
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processSet(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): string {
        $setSql         = [];
        $i              = 0;
        $isPdoDriver    = $driver instanceof PdoDriverInterface;
        $set            = $this->getSet();
        $parameterNames = $this->resolveParameterNames(array_map(strval(...), array_keys($set->toArray())));

        foreach ($set as $column => $value) {
            $prefix = $this->quoteSetColumn($column, $platform);
            $prefix .= ' = ';
            if (is_scalar($value) && $parameterContainer && $driver instanceof DriverInterface) {
                // use incremental value instead of column name for PDO
                // @see https://github.com/zendframework/zend-db/issues/35
                $name = $parameterNames[$column] ?? $column;
                if ($isPdoDriver) {
                    $name = 'c_' . $i++;
                }

                $setSql[] = $prefix . $driver->formatParameterName($name);
                $parameterContainer->offsetSet($name, $value);
                continue;
            }

            $setSql[] = $prefix
            . $this->resolveColumnValue(
                $value,
                $platform,
                $driver,
                $parameterContainer,
            );
        }

        return $this->formatSpecification(
            static::SPECIFICATION_SET,
            implode(', ', $setSql),
        );
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processUpdate(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): string {
        return $this->formatSpecification(
            static::SPECIFICATION_UPDATE,
            $this->resolveTable($this->unaliasTable($this->table), $platform, $driver, $parameterContainer),
        );
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processWhere(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?string {
        if (null === $this->where || $this->where->count() === 0) {
            return null;
        }

        return $this->formatSpecification(
            static::SPECIFICATION_WHERE,
            $this->processExpression($this->where, $platform, $driver, $parameterContainer, 'where'),
        );
    }

    private function getJoins(): Join
    {
        return $this->joins ??= new Join();
    }

    /**
     * @return PriorityList<string, ColumnValue>
     */
    private function getSet(): PriorityList
    {
        if (null === $this->set) {
            $this->set = new PriorityList();
            $this->set->isLIFO(false);
        }
        return $this->set;
    }

    private function getWhere(): Where
    {
        return $this->where ??= new Where();
    }

    /**
     * Quote a SET target as an identifier chain, splitting only on the table qualifier dot
     */
    private function quoteSetColumn(string $column, PlatformInterface $platform): string
    {
        return implode(
            $platform->getIdentifierSeparator(),
            array_map($platform->quoteIdentifier(...), explode('.', $column)),
        );
    }

    /**
     * __clone
     *
     * Resets the where object each time the Update is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        $this->where = null === $this->where ? null : clone $this->where;
        $this->joins = null === $this->joins ? null : clone $this->joins;
        $this->set   = null === $this->set ? null : clone $this->set;
    }

    /**
     * Variable overloading
     * Proxies to "where" only
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            return $this->getWhere();
        }

        return null;
    }
}
