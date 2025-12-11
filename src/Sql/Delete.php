<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function strtolower;

/**
 * @property Where $where
 */
class Delete extends AbstractPreparableSql
{
    protected ?TableIdentifier $table = null;

    protected ?Where $where = null;

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

    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        if (($this->where === null || $this->where->count() === 0) && !$this->where?->isEmptyAllowed()) {
            throw new Exception\InvalidArgumentException(
                'DELETE requires a WHERE clause. Use ->where->setEmptyAllowed() to allow deletion of all rows.'
            );
        }

        $values = [];

        $sql = 'DELETE FROM ' . ($this->table?->toSqlPart() ?? '')
             . ($this->where?->toSqlPart($values) ?? '');

        return $this->quoteSqlString($sql, $values, $platform, $parameterContainer, 'where', $driver);
    }

    public function __get(string $name): ?Where
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
