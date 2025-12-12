<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

final class IsNotNull extends IsNull
{
    protected string $operator = 'IS NOT NULL';
}
