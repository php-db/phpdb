<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class IsNotNull extends IsNull
{
    protected string $specification = '%s IS NOT NULL';
}
