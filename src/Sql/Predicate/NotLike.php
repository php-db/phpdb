<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class NotLike extends Like
{
    protected string $specification = '%1$s NOT LIKE %2$s';
}
