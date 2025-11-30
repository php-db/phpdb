<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

class NotBetween extends Between
{
    protected string $specification = '%s NOT BETWEEN %s AND %s';
}
