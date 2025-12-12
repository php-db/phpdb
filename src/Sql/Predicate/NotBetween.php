<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

final class NotBetween extends Between
{
    protected string $operator = 'NOT BETWEEN';
}
