<?php

declare(strict_types=1);

namespace PhpDb\Sql;

/**
 * @api
 */
interface ExpressionInterface
{
    /**
     * Returns raw expression data as array for optimised processing
     *
     * @return array{spec: string, values: list<ArgumentInterface>}
     */
    public function getExpressionData(): array;
}
