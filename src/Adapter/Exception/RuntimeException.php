<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException
{
    final public const string ALREADY_PREPARED = 'This statement has been prepared already';

    final public const string DISCONNECTED_ROLLBACK = 'Must be connected before you can rollback';

    /** The driver's own error text, passed through verbatim. */
    final public const string DRIVER_ERROR = '%s';

    final public const string FORWARD_ONLY_REWIND =
        'This result is a forward only result set,'
            . ' calling rewind() after moving forward is not supported';

    final public const string INVALID_PDO_PARAM =
        'The PDO param "%s" contains invalid characters.'
            . ' Only alphabetic characters, digits, and underscores (_) are allowed.';

    final public const string MISSING_DSN =
        'The DSN has not been set or constructed from parameters'
            . ' in connect() for this Connection';

    final public const string MISSING_PDO_EXTENSION =
        'The PDO extension is required for this adapter'
            . ' but the extension is not loaded';

    final public const string MISSING_QUERY_RESULT = 'Query execution did not produce a result';

    final public const string NON_QUERY_RESULT =
        'Cannot produce a query result set from a result'
            . ' that is not a query result; check isQueryResult() first';

    final public const string ROLLBACK_WITHOUT_TRANSACTION =
        'Must call beginTransaction()'
            . ' before you can rollback';

    final public const string UNCOMPOSABLE_TRAIT = '%s can only be composed into %s';

    final public const string UNSTARTED_PROFILE = 'A profile must be started before %s can be called.';

    final public const string VULNERABLE_PLATFORM_QUOTE =
        'Attempting to quote in %s::%s'
            . ' without extension/driver support can introduce security vulnerabilities'
            . ' in a production environment.';

    public static function forAlreadyPrepared(): self
    {
        return new self(self::ALREADY_PREPARED);
    }

    public static function forDisconnectedRollback(): self
    {
        return new self(self::DISCONNECTED_ROLLBACK);
    }

    public static function forDriverError(string $error): self
    {
        return new self(sprintf(self::DRIVER_ERROR, $error));
    }

    public static function forForwardOnlyRewind(): self
    {
        return new self(self::FORWARD_ONLY_REWIND);
    }

    public static function forInvalidPdoParam(string $name): self
    {
        return new self(sprintf(self::INVALID_PDO_PARAM, $name));
    }

    public static function forMissingDsn(): self
    {
        return new self(self::MISSING_DSN);
    }

    public static function forMissingPdoExtension(): self
    {
        return new self(self::MISSING_PDO_EXTENSION);
    }

    public static function forMissingQueryResult(): self
    {
        return new self(self::MISSING_QUERY_RESULT);
    }

    public static function forNonQueryResult(): self
    {
        return new self(self::NON_QUERY_RESULT);
    }

    public static function forRollbackWithoutTransaction(): self
    {
        return new self(self::ROLLBACK_WITHOUT_TRANSACTION);
    }

    public static function forUncomposableTrait(string $trait, string $interface): self
    {
        return new self(sprintf(self::UNCOMPOSABLE_TRAIT, $trait, $interface));
    }

    public static function forUnstartedProfile(string $function): self
    {
        return new self(sprintf(self::UNSTARTED_PROFILE, $function));
    }

    public static function forVulnerablePlatformQuote(string $platformName, string $methodName): self
    {
        return new self(sprintf(self::VULNERABLE_PLATFORM_QUOTE, $platformName, $methodName));
    }
}
