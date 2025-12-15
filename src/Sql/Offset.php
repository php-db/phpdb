<?php

declare(strict_types=1);

namespace PhpDb\Sql;

final readonly class Offset
{
    public int $value;

    public function __construct(string|int $value)
    {
        $this->value = (int) $value;
    }

    /**
     * Returns SQL part with embedded value.
     */
    public function prepareSqlString(): string
    {
        return ' OFFSET ' . $this->value;
    }
}
