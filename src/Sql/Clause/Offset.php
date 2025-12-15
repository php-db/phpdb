<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\PreparableSqlInterface;

final readonly class Offset implements PreparableSqlInterface
{
    public int $value;

    public function __construct(string|int $value)
    {
        $this->value = (int) $value;
    }

    /**
     * Returns SQL part with parameterized or embedded value.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if ($builder->isParameterized()) {
            $paramName = 'offset';
            $builder->bindNamedValue($paramName, $this->value);
            return ' OFFSET ' . $builder->formatParameterName($paramName);
        }

        return ' OFFSET ' . $this->value;
    }
}
