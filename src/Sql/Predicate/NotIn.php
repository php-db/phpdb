<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class NotIn extends In
{
    protected string $specification = '%s NOT IN %s';
}
