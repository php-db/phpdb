<?php

declare(strict_types=1);

namespace PhpDb\ResultSet\Exception;

use PhpDb\Exception;

class RuntimeException extends Exception\RuntimeException
{
    final public const string UNBUFFERED_ITERATION = 'Buffering must be enabled before iteration is started';

    public static function forUnbufferedIteration(): self
    {
        return new self(self::UNBUFFERED_ITERATION);
    }
}
