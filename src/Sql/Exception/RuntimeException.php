<?php

declare(strict_types=1);

namespace PhpDb\Sql\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException
{
    final public const string REPLACEMENT_MISMATCH =
        'The number of replacements in the expression'
            . ' does not match the number of parameters';

    final public const string SUBJECT_NOT_IMPLEMENTING =
        'The subject does not appear to implement %s,'
            . ' thus calling %s has no effect';

    final public const string SUBJECT_NOT_PREPARABLE_SQL_INTERFACE =
        'The subject does not implement'
            . ' PreparableSqlInterface';

    final public const string SUBJECT_NOT_SQL_INTERFACE = 'The subject does not implement SqlInterface';

    final public const string UNSUPPORTED_PARAMETER_COUNT =
        'A number of parameters was found'
            . ' that is not supported by this specification';

    final public const string UNSUPPORTED_PARAMETER_COUNT_OF =
        'A number of parameters (%d) was found'
            . ' that is not supported by this specification';

    public static function forReplacementMismatch(): self
    {
        return new self(self::REPLACEMENT_MISMATCH);
    }

    public static function forSubjectNotImplementing(string $interface, string $method): self
    {
        return new self(sprintf(self::SUBJECT_NOT_IMPLEMENTING, $interface, $method));
    }

    public static function forSubjectNotPreparableSqlInterface(): self
    {
        return new self(self::SUBJECT_NOT_PREPARABLE_SQL_INTERFACE);
    }

    public static function forSubjectNotSqlInterface(): self
    {
        return new self(self::SUBJECT_NOT_SQL_INTERFACE);
    }

    public static function forUnsupportedParameterCount(): self
    {
        return new self(self::UNSUPPORTED_PARAMETER_COUNT);
    }

    public static function forUnsupportedParameterCountOf(int $count): self
    {
        return new self(sprintf(self::UNSUPPORTED_PARAMETER_COUNT_OF, $count));
    }
}
