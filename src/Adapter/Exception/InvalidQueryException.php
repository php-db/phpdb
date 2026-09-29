<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use Throwable;

use function sprintf;

class InvalidQueryException extends UnexpectedValueException
{
    /** The driver's own error text, passed through verbatim. */
    final public const string DRIVER_ERROR = '%s';

    final public const string FAILED_STATEMENT = 'Statement could not be executed (%s)';

    public static function forDriverError(string $error): self
    {
        return new self(sprintf(self::DRIVER_ERROR, $error));
    }

    public static function forFailedStatement(string $errorInfo, int $code, Throwable $previous): self
    {
        return new self(sprintf(self::FAILED_STATEMENT, $errorInfo), $code, $previous);
    }
}
