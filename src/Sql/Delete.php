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

    protected TableIdentifier|string|array $table = '';

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
        $this->table = $table;
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
     * Optimized buildSqlString using match expression and string concatenation
     */
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $sql = '';

        foreach ($this->specifications as $name => $specification) {
            // Skip method calls for null/empty properties (avoid function call overhead)
            $result = match ($name) {
                'delete' => $this->processDelete($platform, $driver, $parameterContainer),
                'where' => $this->where !== null && $this->where->count() > 0
                    ? $this->processWhere($platform, $driver, $parameterContainer) : null,
                default => $this->{'process' . $name}($platform, $driver, $parameterContainer),
            };

            if (is_array($result)) {
                $part = $this->createSqlFromSpecificationAndParameters($specification, $result);
                $sql .= $sql === '' ? $part : ' ' . $part;
            } elseif ($result !== null) {
                $sql .= $sql === '' ? $result : ' ' . $result;
            }
        }

        return rtrim($sql, "\n ,");
    }

    protected function processDelete(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return str_replace(
            '%1$s',
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer),
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

        $values       = [];
        $sql          = $this->where->toSqlPart($values);
        $assembledSql = $this->assembleSqlWithValues($sql, $values, $platform, $parameterContainer, 'where', $driver);

        return str_replace(
            '%1$s',
            $assembledSql,
            $this->specifications[static::SPECIFICATION_WHERE]
        );
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
