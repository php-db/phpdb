<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function implode;
use function str_contains;
use function str_replace;

class Group
{
    protected array $columns = [];

    public function add(string|array $column): static
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
     * Build GROUP BY clause with marker-based identifiers for deferred quoting.
     */
    public function toSqlPart(): string
    {
        if ($this->columns === []) {
            return '';
        }

        $lq = PreparableSqlInterface::P_LQUOTE;
        $rq = PreparableSqlInterface::P_RQUOTE;

        $groups = [];
        foreach ($this->columns as $column) {
            // Use markers for deferred quoting - handle table.column format
            if (str_contains($column, '.')) {
                $groups[] = $lq . str_replace('.', $rq . '.' . $lq, $column) . $rq;
            } else {
                $groups[] = $lq . $column . $rq;
            }
        }

        return ' GROUP BY ' . implode(', ', $groups);
    }
}