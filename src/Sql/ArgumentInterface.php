<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface ArgumentInterface
{
    public function getType(): ArgumentType;

    public function getValue(): null|string|int|float|bool|array|ExpressionInterface|SqlInterface;
}
