<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface ArgumentInterface
{
    public function getType(): ArgumentType;

    public function getValue(): ExpressionInterface|SqlInterface|string|int|float|bool|array|null;

    /**
     * Get the SQL specification with markers for deferred quoting.
     *
     * For Identifiers: returns {"identifier"} or {"table"}.{"column"}
     * For Values: returns {?} (value should be collected separately)
     * For Values (multiple): returns ({?}, {?}, ...)
     * For Literals: returns the literal string as-is
     */
    public function getSpecification(): string;
}
