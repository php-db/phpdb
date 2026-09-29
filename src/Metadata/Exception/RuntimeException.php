<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException
{
    final public const string UNKNOWN_COLUMN = 'A column by that name was not found.';

    final public const string UNKNOWN_CONSTRAINT = 'Cannot find a constraint by that name in this table';

    final public const string UNKNOWN_TABLE = 'Table "%s" does not exist';

    final public const string UNKNOWN_TRIGGER = 'Trigger "%s" does not exist';

    final public const string UNKNOWN_VIEW = 'View "%s" does not exist';

    final public const string UNSUPPORTED_TABLE_TYPE = 'Table "%s" is of an unsupported type "%s"';

    public static function forUnknownColumn(): self
    {
        return new self(self::UNKNOWN_COLUMN);
    }

    public static function forUnknownConstraint(): self
    {
        return new self(self::UNKNOWN_CONSTRAINT);
    }

    public static function forUnknownTable(string $tableName): self
    {
        return new self(sprintf(self::UNKNOWN_TABLE, $tableName));
    }

    public static function forUnknownTrigger(string $triggerName): self
    {
        return new self(sprintf(self::UNKNOWN_TRIGGER, $triggerName));
    }

    public static function forUnknownView(string $viewName): self
    {
        return new self(sprintf(self::UNKNOWN_VIEW, $viewName));
    }

    public static function forUnsupportedTableType(string $tableName, string $tableType): self
    {
        return new self(sprintf(self::UNSUPPORTED_TABLE_TYPE, $tableName, $tableType));
    }
}
