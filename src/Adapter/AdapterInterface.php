<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

use PhpDb\ResultSet;

/**
 * @api
 *
 * @property Driver\DriverInterface $driver
 * @property Platform\PlatformInterface $platform
 */
interface AdapterInterface
{
    /**
     * Query Mode Constants
     */
    public const string QUERY_MODE_EXECUTE = 'execute';
    public const string QUERY_MODE_PREPARE = 'prepare';

    /**
     * Prepare Type Constants
     */
    public const string PREPARE_TYPE_POSITIONAL = 'positional';
    public const string PREPARE_TYPE_NAMED      = 'named';

    public const string FUNCTION_FORMAT_PARAMETER_NAME = 'formatParameterName';
    public const string FUNCTION_QUOTE_IDENTIFIER      = 'quoteIdentifier';
    public const string FUNCTION_QUOTE_VALUE           = 'quoteValue';

    public const string VALUE_QUOTE_SEPARATOR = 'quoteSeparator';

    /**
     * @param ParameterContainer|array<array-key, mixed> $initialParameters
     */
    public function createStatement(
        ?string $initialSql = null,
        ParameterContainer|array $initialParameters = [],
    ): Driver\StatementInterface;

    /**
     * Executes raw SQL or a prepared statement.
     *
     * @throws Exception\RuntimeException When execution did not produce a result.
     */
    public function executeQuery(Driver\StatementInterface|string $sql): Driver\ResultInterface;

    public function getDriver(): Driver\DriverInterface;

    /**
     * @todo 0.3.x track down this usage!!!
     * @return array
     */
    public function getHelpers();

    public function getPlatform(): Platform\PlatformInterface;

    public function getProfiler(): ?Profiler\ProfilerInterface;

    public function getQueryResultSetPrototype(): ResultSet\ResultSetInterface;

    /**
     * Prepares a statement for the given SQL without executing it.
     *
     * @param ParameterContainer|array<array-key, mixed> $parameters
     */
    public function prepareQuery(
        string $sql,
        ParameterContainer|array $parameters = [],
    ): Driver\StatementInterface;

    /**
     * @param ParameterContainer|array<array-key, mixed>|string $parametersOrQueryMode
     */
    public function query(
        string $sql,
        ParameterContainer|array|string $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?ResultSet\ResultSetInterface $resultPrototype = null,
    ): Driver\StatementInterface|ResultSet\ResultSetInterface|Driver\ResultInterface;
}
