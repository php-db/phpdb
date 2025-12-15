<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Clause\JoinClause;
use PhpDb\Sql\Clause\WhereClause;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function implode;
use function is_scalar;
use function strtolower;

/**
 * @property WhereClause $where
 */
class Update extends AbstractPreparableSql
{
    public const VALUES_MERGE = 'merge';

    public const VALUES_SET = 'set';

    protected ?TableIdentifier $table = null;

    protected ?Set $set = null;

    protected ?WhereClause $where = null;

    protected ?JoinClause $joins = null;

    protected bool $emptyWhereAllowed = false;

    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    private function getSet(): Set
    {
        return $this->set ??= new Set();
    }

    private function getWhere(): WhereClause
    {
        return $this->where ??= new WhereClause();
    }

    private function getJoins(): JoinClause
    {
        return $this->joins ??= new JoinClause();
    }

    public function table(TableIdentifier|string|array $table): static
    {
        $this->table = TableIdentifier::from($table);
        return $this;
    }

    public function set(array $values, string|int $flag = self::VALUES_SET): static
    {
        $this->getSet()->set($values, $flag);
        return $this;
    }

    public function where(
        PredicateInterface|array|Closure|string|WhereClause $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        if ($predicate instanceof WhereClause) {
            $this->where = $predicate;
        } else {
            $this->getWhere()->addPredicates($predicate, $combination);
        }

        return $this;
    }

    public function join(array|string|TableIdentifier $name, string $on, string $type = JoinClause::JOIN_INNER): static
    {
        $this->getJoins()->join($name, $on, [], $type);

        return $this;
    }

    /**
     * Allow UPDATE without a WHERE clause (updates all rows).
     */
    public function setEmptyWhereAllowed(bool $allowed = true): static
    {
        $this->emptyWhereAllowed = $allowed;
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'table' => $this->table,
            'set'   => $this->set,
            'where' => $this->where,
            'joins' => $this->joins,
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        if (! $this->emptyWhereAllowed && ($this->where === null || $this->where->count() === 0)) {
            throw new Exception\InvalidArgumentException(
                'UPDATE requires a WHERE clause. Use ->setEmptyWhereAllowed() to allow updating all rows.'
            );
        }

        $q = $platform->getQuoteIdentifierSymbol();

        return 'UPDATE ' . ($this->table?->prepareSqlString($q) ?? '')
             . ($this->joins?->prepareSqlString($q, $platform) ?? '')
             . $this->buildSetPart($platform, $driver, $parameterContainer)
             . ($this->where?->prepareSqlString($q, $platform) ?? '');
    }

    protected function buildSetPart(
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

        return ' SET ' . implode(', ', $setSql);
    }

    public function __get(string $name): ?WhereClause
    {
        if (strtolower($name) === 'where') {
            return $this->getWhere();
        }

        return null;
    }

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
