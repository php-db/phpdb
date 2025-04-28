<?php

namespace Laminas\Db\Sql;

use Closure;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function sprintf;
use function strtolower;

/**
 * @property Where $where
 */
class Delete extends AbstractPreparableSql
{
    /**@#+
     * @const
     */
    public const SPECIFICATION_DELETE = 'delete';
    public const SPECIFICATION_WHERE  = 'where';
    /**@#-*/

    /**
     * {@inheritDoc}
     */
    protected array $specifications = [
        self::SPECIFICATION_DELETE => 'DELETE FROM %1$s',
        self::SPECIFICATION_WHERE  => 'WHERE %1$s',
    ];

    protected TableIdentifier|string|array $table = '';

    /** @var bool */
    protected $emptyWhereProtection = true;

    /** @var array */
    protected $set = [];

    /** @var null|string|Where */
    protected $where;

    /**
     * Constructor
     *
     * @param  null|string|TableIdentifier $table
     */
    public function __construct($table = null)
    {
        if ($table) {
            $this->from($table);
        }
        $this->where = new Where();
    }

    /**
     * Create from statement
     *
     * @param  string|array|TableIdentifier $table
     * @return $this Provides a fluent interface
     */
    public function from($table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRawState(?string $key = null)
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table,
            'set'                  => $this->set,
            'where'                => $this->where,
        ];
        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Create where clause
     *
     * @param Where|Closure|string|array|PredicateInterface $predicate
     * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
     * @return $this Provides a fluent interface
     */
    public function where($predicate, $combination = Predicate\PredicateSet::OP_AND)
    {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            $this->where->addPredicates($predicate, $combination);
        }
        return $this;
    }

    /**
     * @return string
     */
    protected function processDelete(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ) {
        return sprintf(
            $this->specifications[static::SPECIFICATION_DELETE],
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer)
        );
    }

    /**
     * @return null|string
     */
    protected function processWhere(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ) {
        if ($this->where->count() === 0) {
            return null;
        }

        return sprintf(
            $this->specifications[static::SPECIFICATION_WHERE],
            $this->processExpression($this->where, $platform, $driver, $parameterContainer, 'where')
        );
    }

    /**
     * Property overloading
     *
     * Overloads "where" only.
     *
     * @param  string $name
     * @return Where|null
     */
    public function __get($name)
    {
        if (strtolower($name) === 'where') {
            return $this->where;
        }
        return null;
    }
}
