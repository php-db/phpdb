<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use function str_contains;
use function str_replace;

final readonly class GroupExpression
{
    public function __construct(
        public string $column
    ) {
    }

    /**
     * Build SQL for this group column.
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function toSql(string $q): string
    {
        return str_contains($this->column, '.')
            ? $q . str_replace('.', $q . '.' . $q, $this->column) . $q
            : $q . $this->column . $q;
    }
}
