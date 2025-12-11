<?php

declare(strict_types=1);

namespace PhpDb\Sql;

class Offset
{
    protected int $value;

    public function __construct(string|int $value)
    {
        $this->value = (int) $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Returns SQL with marker and populates values array for deferred processing.
     */
    public function toSqlPart(array &$values): string
    {
        $values[] = $this->value;
        return ' OFFSET ' . PreparableSqlInterface::P_VALUE;
    }
}