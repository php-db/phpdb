<?php

declare(strict_types=1);

namespace PhpDb\ResultSet\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException
{
    final public const string UNBUFFERED_ITERATION = 'Buffering must be enabled before iteration is started';

    final public const string UNINITIALISED_DATA_SOURCE = 'The result set has no data source; call initialize() first';

    public static function forUnbufferedIteration(): self
    {
        return new self(self::UNBUFFERED_ITERATION);
    }

    final public const string UNHYDRATABLE_ROW = 'Cannot extract a row of type "%s"; the hydrator requires an object';

    public static function forUnhydratableRow(string $type): self
    {
        return new self(sprintf(self::UNHYDRATABLE_ROW, $type));
    }

    public static function forUninitialisedDataSource(): self
    {
        return new self(self::UNINITIALISED_DATA_SOURCE);
    }
}
