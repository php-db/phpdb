<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

interface ConstraintInterface extends ExpressionInterface
{
    public function getColumns(): array;

    /**
     * Build the constraint definition SQL using the builder.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string;
}
