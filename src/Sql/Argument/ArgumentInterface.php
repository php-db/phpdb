<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\SqlInterface;

interface ArgumentInterface
{
    public function getType(): ArgumentType;

    public function getValue(): null|string|int|float|bool|array|ExpressionInterface|SqlInterface;
}
