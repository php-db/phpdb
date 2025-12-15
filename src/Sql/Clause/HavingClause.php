<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\Predicate\Predicate;

class HavingClause extends Predicate implements ClauseInterface
{
    protected string $prefix = 'HAVING';
}