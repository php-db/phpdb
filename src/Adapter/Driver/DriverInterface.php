<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Exception;

/**
 * @api
 *
 * @template TConnection of object|resource = object|resource The native connection handle
 * @template TStatement of object|resource = object|resource The native prepared statement handle
 * @template TResult of object|resource = object|resource The native result handle
 */
interface DriverInterface
{
    final public const string PARAMETERIZATION_POSITIONAL = 'positional';
    final public const string PARAMETERIZATION_NAMED      = 'named';

    /**
     * Check environment
     *
     * @throws Exception\RuntimeException
     */
    public function checkEnvironment(): bool;

    /**
     * Create result
     *
     * @param TResult $resource The driver's native result handle
     *
     * @return ResultInterface<TResult>
     */
    public function createResult(mixed $resource): ResultInterface;

    /**
     * Create statement
     *
     * @param TStatement|string|null $sqlOrResource
     *
     * @return StatementInterface<TStatement>
     */
    public function createStatement(mixed $sqlOrResource = null): StatementInterface;

    /** Format parameter name */
    public function formatParameterName(string $name, ?string $type = null): string;

    /**
     * Get connection
     *
     * @return ConnectionInterface<TConnection>
     */
    public function getConnection(): ConnectionInterface;

    /** Get last generated value */
    public function getLastGeneratedValue(): string|int|false|null;

    /** Get prepare type */
    public function getPrepareType(): string;
}
