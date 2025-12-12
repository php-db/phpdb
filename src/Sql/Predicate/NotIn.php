<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

final class NotIn extends In
{
    protected string $operator = 'NOT IN';
}
