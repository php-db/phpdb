<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Exception;

interface DriverInterface
{
    public const PARAMETERIZATION_POSITIONAL = 'positional';
    public const PARAMETERIZATION_NAMED      = 'named';
    public const NAME_FORMAT_CAMELCASE       = 'camelCase';
    public const NAME_FORMAT_NATURAL         = 'natural';

    /** Get database platform name */
    public function getDatabasePlatformName(string $nameFormat = DriverInterface::NAME_FORMAT_CAMELCASE): string;

    /**
     * Check environment
     *
     * @throws Exception\RuntimeException
     */
    public function checkEnvironment(): bool;

    /** Get connection */
    public function getConnection(): ConnectionInterface;

    /**
     * Create statement
     *
     * @param resource|string $sqlOrResource
     */
    public function createStatement($sqlOrResource = null): StatementInterface;

    /**
     * Create result
     *
     * @param resource $resource
     */
    public function createResult($resource): ResultInterface;

    /** Get prepare type */
    public function getPrepareType(): string;

    /** Format parameter name */
    public function formatParameterName(string $name, ?string $type = null): string;

    /**
     * Get last generated value
     *
     * todo: narrow this to int|string|false|null once 8.2 is minimum
     * */
    public function getLastGeneratedValue(): int|string|bool|null;
}
