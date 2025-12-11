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
use function is_array;
use function rtrim;
use function str_replace;
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

    final public const SPECIFICATION_WHERE = 'where';

    /**@#-*/

    /**
     * {@inheritDoc}
     */
    protected array $specifications = [
        self::SPECIFICATION_DELETE => 'DELETE FROM %1$s',
        self::SPECIFICATION_WHERE  => 'WHERE %1$s',
    ];

    protected ?TableIdentifier $table = null;

    protected bool $emptyWhereProtection = true;

    protected ?Where $where = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->from($table);
        }
    }

    private function getWhere(): Where
    {
        return $this->where ??= new Where();
    }

    /**
     * Create from statement
     */
    public function from(TableIdentifier|string|array $table): static
    {
        $this->table = TableIdentifier::from($table);
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table,
            'where'                => $this->getWhere(),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Create where clause
     *
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
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
     * Optimized buildSqlString using direct concatenation with coalescing
     */
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $values = [];

        // Build DELETE FROM table part
        $tableSql = $this->table !== null
            ? $this->table->toSqlPart()
            : '';

        // Build complete SQL using direct concatenation
        $sql = 'DELETE FROM ' . $tableSql
             . ($this->where?->toSqlPart($values) ?? '');

        return $this->assembleSqlWithValues($sql, $values, $platform, $parameterContainer, 'where', $driver);
    }

    protected function processDelete(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        // Use TableIdentifier's toSqlPart() and assemble with platform
        $tableSql = $this->table !== null
            ? $this->assembleSqlWithValues($this->table->toSqlPart(), [], $platform, null, 'table')
            : '';

        return str_replace(
            '%1$s',
            $tableSql,
            $this->specifications[static::SPECIFICATION_DELETE]
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

        $values = [];
        $sql    = $this->where->toSqlPart($values);

        // toSqlPart already includes " WHERE " prefix, just assemble and return
        return $this->assembleSqlWithValues($sql, $values, $platform, $parameterContainer, 'where', $driver);
    }

    /**
     * Property overloading
     * Overloads "where" only.
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            return $this->getWhere();
        }

        return null;
    }
}
