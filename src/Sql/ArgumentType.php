<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;

/**
 * Defines types for SQL argument handling in expressions and statements.
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
    case Identifier  = Identifier::class;
    case Identifiers = Identifiers::class;
    case Value       = Value::class;
    case Values      = Values::class;
    case Literal     = Literal::class;
    case Select      = Select::class;
}
