<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Exception;

use PhpDb\Exception;

use function sprintf;

class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string INVALID_COLUMN = 'Not a valid column in this row: %s';

    final public const string SQL_TABLE_MISMATCH =
        'The Sql object provided does not have a table'
            . ' that matches this row object';

    public static function forInvalidColumn(string $name): self
    {
        return new self(sprintf(self::INVALID_COLUMN, $name));
    }

    public static function forSqlTableMismatch(): self
    {
        return new self(self::SQL_TABLE_MISMATCH);
    }
}
