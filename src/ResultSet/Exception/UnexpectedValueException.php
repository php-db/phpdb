<?php

declare(strict_types=1);

namespace PhpDb\ResultSet\Exception;

use PhpDb\Exception;

use function sprintf;

final class UnexpectedValueException extends Exception\UnexpectedValueException
{
    final public const string UNREADABLE_ROW = 'A row of type "%s" exposes no values for %s to read, so it cannot be cast to an array; iterate the result set instead of calling toArray()';

    final public const string ROW_THAT_IS_NOT_AN_OBJECT = 'A row of type "%s" cannot be used by %s, which accepts an object; a result set will not transform a row it did not create, so select a fetch mode that yields a supported type';

    final public const string ROW_THAT_IS_NOT_ARRAY_DATA = 'A row of type "%s" cannot be used by %s, which accepts an array or ArrayObject; a result set will not transform a row it did not create, so select a fetch mode that yields a supported type';

    final public const string ROW_THAT_IS_NOT_ARRAY_DATA_OR_PROTOTYPE = 'A row of type "%s" cannot be used by %s, which accepts an array, ArrayObject or RowPrototypeInterface; a result set will not transform a row it did not create, so select a fetch mode that yields a supported type';

    public static function forRowThatIsNotAnObject(string $rowType, string $resultSetType): self
    {
        return new self(sprintf(self::ROW_THAT_IS_NOT_AN_OBJECT, $rowType, $resultSetType));
    }

    public static function forRowThatIsNotArrayData(string $rowType, string $resultSetType): self
    {
        return new self(sprintf(self::ROW_THAT_IS_NOT_ARRAY_DATA, $rowType, $resultSetType));
    }

    public static function forRowThatIsNotArrayDataOrPrototype(string $rowType, string $resultSetType): self
    {
        return new self(sprintf(self::ROW_THAT_IS_NOT_ARRAY_DATA_OR_PROTOTYPE, $rowType, $resultSetType));
    }

    public static function forUnreadableRow(string $rowType, string $resultSetType): self
    {
        return new self(sprintf(self::UNREADABLE_ROW, $rowType, $resultSetType));
    }
}
