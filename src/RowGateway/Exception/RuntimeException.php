<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException
{
    final public const string MISSING_PRIMARY_KEY_COLUMN =
        'This row object does not have'
            . ' a primary key column set.';

    final public const string MISSING_PRIMARY_KEY_DATA =
        'While processing primary key data, a known key %s'
            . ' was not found in the data array';

    final public const string MISSING_SQL_OBJECT = 'This row object does not have a Sql object set.';

    final public const string MISSING_TABLE = 'This row object does not have a valid table set.';

    final public const string UNCALLABLE_METHOD = 'This method is not intended to be called on this object.';

    public static function forMissingPrimaryKeyColumn(): self
    {
        return new self(self::MISSING_PRIMARY_KEY_COLUMN);
    }

    public static function forMissingPrimaryKeyData(string $column): self
    {
        return new self(sprintf(self::MISSING_PRIMARY_KEY_DATA, $column));
    }

    public static function forMissingSqlObject(): self
    {
        return new self(self::MISSING_SQL_OBJECT);
    }

    public static function forMissingTable(): self
    {
        return new self(self::MISSING_TABLE);
    }

    public static function forUncallableMethod(): self
    {
        return new self(self::UNCALLABLE_METHOD);
    }
}
