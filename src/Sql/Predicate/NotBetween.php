<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class NotBetween extends Between
{
    protected string $operator = 'NOT BETWEEN';
}
