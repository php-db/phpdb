<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

/**
 * Interface ColumnInterface describes the protocol on how Column objects interact
 */
interface ColumnInterface extends ExpressionInterface
{
    public function getName(): string;

    public function isNullable(): bool;

    public function getDefault(): string|int|null;

    public function getOptions(): array;

    /**
     * Build the column definition SQL using the builder.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string;
}
