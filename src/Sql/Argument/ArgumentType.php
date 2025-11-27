<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

/**
 * Defines types for SQL argument handling in expressions and statements.
 *
 * Specifies how values should be treated during SQL generation:
 * - Identifier: Column names, table names, and other identifiers that require quoting
 * - Identifiers: Multiple identifiers for multi-column clauses (e.g., multi-column IN predicates)
 * - Value: Data values that should be parameterized or escaped
 * - Values: Multiple values for IN clauses and similar constructs
 * - Literal: Raw SQL fragments that are inserted as-is without modification
 * - Select: Subquery objects (Expression or SqlInterface instances)
 */
enum ArgumentType: string
{
    case Identifier  = IdentifierArgument::class;
    case Identifiers = IdentifiersArgument::class;
    case Value       = ValueArgument::class;
    case Values      = ValuesArgument::class;
    case Literal     = LiteralArgument::class;
    case Select      = SelectArgument::class;
}
