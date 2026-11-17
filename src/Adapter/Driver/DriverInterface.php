<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Exception;

/**
 * @api
 */
interface DriverInterface
{
    public const string PARAMETERIZATION_POSITIONAL = 'positional';
    public const string PARAMETERIZATION_NAMED      = 'named';

    /**
     * Check environment
     *
     * @throws Exception\RuntimeException
     */
    public function checkEnvironment(): bool;

    /**
     * Create result
     *
     * @param resource|object $resource The driver's native result handle
     */
    public function createResult(mixed $resource): ResultInterface;

    /**
     * Create statement
     *
     * @param resource|object|string|null $sqlOrResource
     */
    public function createStatement(mixed $sqlOrResource = null): StatementInterface;

    /** Format parameter name */
    public function formatParameterName(string $name, ?string $type = null): string;

    /** Get connection */
    public function getConnection(): ConnectionInterface;

    /** Get last generated value */
    public function getLastGeneratedValue(): string|int|false|null;

    /** Get prepare type */
    public function getPrepareType(): string;
}
