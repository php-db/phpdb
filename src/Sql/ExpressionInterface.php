<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface ExpressionInterface
{
    public function getExpressionData(): ExpressionData;
}
