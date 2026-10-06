<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use PhpDb\Sql\ExpressionInterface;

interface ConstraintInterface extends ExpressionInterface
{
    /** @return list<string> */
    public function getColumns(): array;
}
