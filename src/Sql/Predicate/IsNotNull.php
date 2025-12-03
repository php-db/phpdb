<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class IsNotNull extends IsNull
{
    protected string $operator = 'IS NOT NULL';
}
