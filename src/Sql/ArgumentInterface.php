<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface ArgumentInterface
{
    public function getType(): ArgumentType;

    public function getValue(): ExpressionInterface|SqlInterface|string|int|float|bool|array|null;

    public function getSpecification(): string;
}
