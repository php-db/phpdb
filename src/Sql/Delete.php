<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;

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

    public const SPECIFICATION_WHERE = 'where';

    /**@#-*/

    /**
     * {@inheritDoc}
     */
    protected array $specifications = [
        self::SPECIFICATION_DELETE => 'DELETE FROM %1$s',
        self::SPECIFICATION_WHERE  => 'WHERE %1$s',
    ];

    protected TableIdentifier|string|array $table = '';

    protected bool $emptyWhereProtection = true;

    protected array $set = [];

    protected Where $where;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->from($table);
        }

        $this->where = new Where();
    }

    /**
     * Create from statement
     *
     * @return $this Provides a fluent interface
     */
    public function from(TableIdentifier|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function getRawState(?string $key = null): mixed
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
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     * @return $this Provides a fluent interface
     */
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            $this->where->addPredicates($predicate, $combination);
        }

        return $this;
    }

    protected function processDelete(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return sprintf(
            $this->specifications[static::SPECIFICATION_DELETE],
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer)
        );
    }

    protected function processWhere(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?string {
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
     * Overloads "where" only.
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            return $this->where;
        }

        return null;
    }
}
