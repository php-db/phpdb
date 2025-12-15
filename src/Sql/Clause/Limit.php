<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\PreparableSqlInterface;

final readonly class Limit implements PreparableSqlInterface
{
    public int $value;

    public function __construct(string|int $value)
    {
        $this->value = (int) $value;
    }

    /**
     * Returns SQL part with embedded value.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        return ' LIMIT ' . $this->value;
    }
}
