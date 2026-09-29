<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate\Exception;

use PhpDb\Sql\Exception;

class RuntimeException extends Exception\RuntimeException
{
    final public const string NOT_NESTED = 'Not nested';

    public static function forNotNested(): self
    {
        return new self(self::NOT_NESTED);
    }
}
