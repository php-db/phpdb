<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

/**
 * @api
 */
class NotLike extends Like
{
    protected string $operator = 'NOT LIKE';
}
