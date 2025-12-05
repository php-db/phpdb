<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use PhpDb\Sql\ExpressionInterface;

interface ConstraintInterface extends ExpressionInterface
{
    public function getColumns(): array;
}
