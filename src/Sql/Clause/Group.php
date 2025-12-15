<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ClauseInterface;

use function count;
use function is_array;

final class Group implements ClauseInterface
{
    /** @var GroupExpression[] */
    protected array $items = [];

    public function add(string|array $column): static
    {
        if (is_array($column)) {
            foreach ($column as $c) {
                $this->items[] = new GroupExpression($c);
            }
        } else {
            $this->items[] = new GroupExpression($column);
        }
        return $this;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get column names for testing/inspection.
     *
     * @return string[]
     */
    public function getColumns(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $item->column;
        }
        return $result;
    }

    /**
     * Build GROUP BY clause.
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function prepareSqlString(string $q): string
    {
        if ($this->items === []) {
            return '';
        }

        $sql   = ' GROUP BY ';
        $first = true;
        foreach ($this->items as $item) {
            if (! $first) {
                $sql .= ', ';
            }
            $sql  .= $item->toSql($q);
            $first = false;
        }

        return $sql;
    }
}