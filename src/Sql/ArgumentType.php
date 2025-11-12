<?php

declare(strict_types=1);

namespace PhpDb\Sql;

/**
 * Defines types for SQL argument handling in expressions and statements.
 *
 * Specifies how values should be treated during SQL generation:
 * - Identifier: Column names, table names, and other identifiers that require quoting
 * - Value: Data values that should be parameterized or escaped
 * - Literal: Raw SQL fragments that are inserted as-is without modification
 * - Select: Subquery objects (Expression or SqlInterface instances)
 */
enum ArgumentType: string
{
    case Identifier = 'identifier';
    case Value      = 'value';
    case Literal    = 'literal';
    case Select     = 'select';
}
