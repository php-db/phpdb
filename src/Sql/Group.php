<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function count;
use function is_array;

final class Group
{
    /** @var GroupColumn[] */
    protected array $items = [];

    public function add(string|array $column): static
    {
        if (is_array($column)) {
            foreach ($column as $c) {
                $this->items[] = new GroupColumn($c);
            }
        } else {
            $this->items[] = new GroupColumn($column);
        }
        return $this;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Build GROUP BY clause.
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function toSqlPart(string $q): string
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
