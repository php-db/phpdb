<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Exception;

use PhpDb\Exception;

use function sprintf;

class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string INVALID_MAGIC_CALL = 'Invalid method (%s) called, caught by %s::__call()';

    final public const string INVALID_MAGIC_GET = 'Invalid magic property access in %s::__get()';

    final public const string INVALID_MAGIC_SET = 'Invalid magic property access in %s::__set()';

    final public const string SQL_TABLE_MISMATCH =
        'The table inside the provided Sql object'
            . ' must match the table of this TableGateway';

    public static function forInvalidMagicCall(string $method, string $class): self
    {
        return new self(sprintf(self::INVALID_MAGIC_CALL, $method, $class));
    }

    public static function forInvalidMagicGet(string $class): self
    {
        return new self(sprintf(self::INVALID_MAGIC_GET, $class));
    }

    public static function forInvalidMagicSet(string $class): self
    {
        return new self(sprintf(self::INVALID_MAGIC_SET, $class));
    }

    public static function forSqlTableMismatch(): self
    {
        return new self(self::SQL_TABLE_MISMATCH);
    }
}
