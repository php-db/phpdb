<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use PhpDb\Sql\ExpressionInterface;

/**
 * Interface ColumnInterface describes the protocol on how Column objects interact
 */
interface ColumnInterface extends ExpressionInterface
{
    public function getName(): string;

    public function isNullable(): bool;

    public function getDefault(): string|int|null;

    public function getOptions(): array;
}
