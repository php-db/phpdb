<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Clause\WhereClause;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function strtolower;

/**
 * @property WhereClause $where
 */
class Delete extends AbstractPreparableSql
{
    protected ?TableIdentifier $table = null;

    protected ?WhereClause $where = null;

    protected bool $emptyWhereAllowed = false;

    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->from($table);
        }
    }

    private function getWhere(): WhereClause
    {
        return $this->where ??= new WhereClause();
    }

    public function from(TableIdentifier|string|array $table): static
    {
        $this->table = TableIdentifier::from($table);
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'table' => $this->table,
            'where' => $this->where,
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
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

    /**
     * Allow DELETE without a WHERE clause (deletes all rows).
     */
    public function setEmptyWhereAllowed(bool $allowed = true): static
    {
        $this->emptyWhereAllowed = $allowed;
        return $this;
    }

    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        if (! $this->emptyWhereAllowed && ($this->where === null || $this->where->count() === 0)) {
            throw new Exception\InvalidArgumentException(
                'DELETE requires a WHERE clause. Use ->setEmptyWhereAllowed() to allow deletion of all rows.'
            );
        }

        $q = $platform->getQuoteIdentifierSymbol();

        return 'DELETE FROM ' . ($this->table?->prepareSqlString($q) ?? '')
             . ($this->where?->prepareSqlString($q, $platform) ?? '');
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
    }
}
