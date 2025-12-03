<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class NotLike extends Like
{
    protected string $operator = 'NOT LIKE';
}
