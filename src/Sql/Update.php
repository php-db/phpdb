<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use Laminas\Stdlib\PriorityList;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;

use function array_key_exists;
use function implode;
use function is_numeric;
use function is_scalar;
use function is_string;
use function str_replace;
use function strtolower;

/**
 * @property Where $where
 */
class Update extends AbstractPreparableSql
{
    /**@#++
     * @const
     */
    public const SPECIFICATION_UPDATE = 'update';

    final public const SPECIFICATION_SET = 'set';

    final public const SPECIFICATION_WHERE = 'where';

    final public const SPECIFICATION_JOIN = 'joins';

    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    /**@#-**/

    /** @var array<string, string>|array<string, array> */
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

    protected TableIdentifier|string|array $table = '';

    protected bool $emptyWhereProtection = true;

    protected ?PriorityList $set = null;

    protected ?Where $where = null;

    protected ?Join $joins = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    private function getSet(): PriorityList
    {
        if ($this->set === null) {
            $this->set = new PriorityList();
            $this->set->isLIFO(false);
        }
        return $this->set;
    }

    private function getWhere(): Where
    {
        return $this->where ??= new Where();
    }

    private function getJoins(): Join
    {
        return $this->joins ??= new Join();
    }

    /**
     * Specify table for statement
     */
    public function table(TableIdentifier|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set key/value pairs to update
     *
     * @param  array $values Associative array of key values
     * @param string $flag   One of the VALUES_* constants
     * @throws Exception\InvalidArgumentException
     */
    public function set(array $values, string|int $flag = self::VALUES_SET): static
    {
        $set = $this->getSet();
        if ($flag === self::VALUES_SET) {
            $set->clear();
        }

        $priority = is_numeric($flag) ? $flag : 0;
        foreach ($values as $k => $v) {
            if (! is_string($k)) {
                throw new Exception\InvalidArgumentException('set() expects a string for the value key');
            }

            $set->insert($k, $v, $priority);
        }

        return $this;
    }

    /**
     * Create where clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            $this->getWhere()->addPredicates($predicate, $combination);
        }

        return $this;
    }

    /**
     * Create join clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function join(array|string|TableIdentifier $name, string $on, string $type = Join::JOIN_INNER): static
    {
        $this->getJoins()->join($name, $on, [], $type);

        return $this;
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
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    protected function processUpdate(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return str_replace(
            '%1$s',
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer),
            $this->specifications[static::SPECIFICATION_UPDATE]
        );
    }

    protected function processSet(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $setSql      = [];
        $i           = 0;
        $isPdoDriver = $driver instanceof PdoDriverInterface;

        foreach ($this->getSet() as $column => $value) {
            $prefix  = $this->resolveColumnValue(
                [
                    'column'       => $column,
                    'fromTable'    => '',
                    'isIdentifier' => true,
                ],
                $platform,
                $driver,
                $parameterContainer,
                'column'
            );
            $prefix .= ' = ';
            if (is_scalar($value) && $parameterContainer) {
                // use incremental value instead of column name for PDO
                // @see https://github.com/zendframework/zend-db/issues/35
                if ($isPdoDriver) {
                    $column = 'c_' . $i++;
                }

                $setSql[] = $prefix . $driver->formatParameterName($column);
                $parameterContainer->offsetSet($column, $value);
            } else {
                $setSql[] = $prefix . $this->resolveColumnValue(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer
                );
            }
        }

        return str_replace(
            '%1$s',
            implode(', ', $setSql),
            $this->specifications[static::SPECIFICATION_SET]
        );
    }

    protected function processWhere(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?string {
        if ($this->where === null || $this->where->count() === 0) {
            return null;
        }

        return str_replace(
            '%1$s',
            $this->processExpression($this->where, $platform, $driver, $parameterContainer, 'where'),
            $this->specifications[static::SPECIFICATION_WHERE]
        );
    }

    /** @return string[][][]|null */
    protected function processJoins(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?array {
        return $this->processJoin($this->joins, $platform, $driver, $parameterContainer);
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

    /**
     * __clone
     *
     * Resets the where object each time the Update is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
        if ($this->joins !== null) {
            $this->joins = clone $this->joins;
        }
        if ($this->set !== null) {
            $this->set = clone $this->set;
        }
    }
}
