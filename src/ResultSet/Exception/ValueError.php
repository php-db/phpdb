<?php

declare(strict_types=1);

namespace PhpDb\ResultSet\Exception;

use PhpDb\Exception;

use function sprintf;

final class ValueError extends Exception\ValueError
{
    final public const string UNREADABLE_ROW = 'A row of type "%s" exposes no values for %s to read, so it cannot be cast to an array; iterate the result set instead of calling toArray()';

    final public const string UNSUPPORTED_ROW = 'A row of type "%s" cannot be used by %s, which accepts %s; a result set will not transform a row it did not create, so select a fetch mode that yields a supported type';

    public static function forRowThatIsNotAnObject(string $rowType, string $resultSetType): self
    {
        return self::forUnsupportedRow($rowType, $resultSetType, 'an object');
    }

    public static function forRowThatIsNotArrayData(string $rowType, string $resultSetType): self
    {
        return self::forUnsupportedRow($rowType, $resultSetType, 'an array or ArrayObject');
    }

    public static function forRowThatIsNotArrayDataOrPrototype(string $rowType, string $resultSetType): self
    {
        return self::forUnsupportedRow(
            $rowType,
            $resultSetType,
            'an array, ArrayObject or RowPrototypeInterface',
        );
    }

    public static function forUnreadableRow(string $rowType, string $resultSetType): self
    {
        return new self(sprintf(self::UNREADABLE_ROW, $rowType, $resultSetType));
    }

    private static function forUnsupportedRow(string $rowType, string $resultSetType, string $accepted): self
    {
        return new self(sprintf(self::UNSUPPORTED_ROW, $rowType, $resultSetType, $accepted));
    }
}
