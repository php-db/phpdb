<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ClauseInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

use function count;
use function implode;
use function is_array;

final class Group implements ClauseInterface
{
    /** @var array<string|ExpressionInterface> */
    protected array $columns = [];

    public function add(string|array|ExpressionInterface $column): static
    {
        if (is_array($column)) {
            foreach ($column as $c) {
                $this->columns[] = $c;
            }
        } else {
            $this->columns[] = $column;
        }
        return $this;
    }

    public function count(): int
    {
        return count($this->columns);
    }

    /**
     * Get column values for testing/inspection.
     *
     * @return array<string|ExpressionInterface>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Build GROUP BY clause.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if ($this->columns === []) {
            return '';
        }

        $parts = [];
        $q     = $builder->q;

        foreach ($this->columns as $column) {
            $parts[] = $column instanceof ExpressionInterface
                ? $builder->processExpression($column)
                : PreparableSqlBuilder::quoteId($column, $q);
        }

        $groupSql = implode(', ', $parts);

        return " GROUP BY {$groupSql}";
    }
}
