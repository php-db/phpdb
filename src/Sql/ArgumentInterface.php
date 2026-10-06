<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface ArgumentInterface
{
    public function getSpecification(): string;

    public function getType(): ArgumentType;

    /** @return ExpressionInterface|SqlInterface|bool|string|float|int|null|list<bool|string|float|int|null> */
    public function getValue(): ExpressionInterface|SqlInterface|string|int|float|bool|array|null;
}
