<?php

declare(strict_types=1);

namespace PhpDb\Sql;

class Where extends Predicate\Predicate
{
    protected string $prefix = 'WHERE';
}
