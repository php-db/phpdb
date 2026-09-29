<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use PhpDb\Exception;

class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string INCORRECT_FLAG = 'Flag incorrectly set';

    final public const string INVALID_FETCH_MODE = 'The fetch mode must be one of the PDO::FETCH_* constants.';

    final public const string INVALID_KEY_TYPE = 'Keys must be string, integer or null';

    final public const string INVALID_MAGIC_PROPERTY = 'Invalid magic property on adapter';

    final public const string INVALID_STATEMENT_MODE = 'The statement mode must be one of the defined constants.';

    final public const string MISSING_DATA = 'Data does not exist for this name/position';

    public static function forIncorrectFlag(): self
    {
        return new self(self::INCORRECT_FLAG);
    }

    public static function forInvalidFetchMode(): self
    {
        return new self(self::INVALID_FETCH_MODE);
    }

    public static function forInvalidKeyType(): self
    {
        return new self(self::INVALID_KEY_TYPE);
    }

    public static function forInvalidMagicProperty(): self
    {
        return new self(self::INVALID_MAGIC_PROPERTY);
    }

    public static function forInvalidStatementMode(): self
    {
        return new self(self::INVALID_STATEMENT_MODE);
    }

    public static function forMissingData(): self
    {
        return new self(self::MISSING_DATA);
    }
}
