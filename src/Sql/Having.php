<?php

declare(strict_types=1);

namespace PhpDb\Sql;

class Having extends Predicate\Predicate
{
    protected string $prefix = 'HAVING';
}
