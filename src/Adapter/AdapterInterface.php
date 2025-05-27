<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter;

/**
 * @property Driver\DriverInterface $driver
 * @property Platform\PlatformInterface $platform
 */
interface AdapterInterface
{
    /**
     * Query Mode Constants
     */
    public const QUERY_MODE_EXECUTE = 'execute';
    public const QUERY_MODE_PREPARE = 'prepare';

    /**
     * Prepare Type Constants
     */
    public const PREPARE_TYPE_POSITIONAL = 'positional';
    public const PREPARE_TYPE_NAMED      = 'named';

    public const FUNCTION_FORMAT_PARAMETER_NAME = 'formatParameterName';
    public const FUNCTION_QUOTE_IDENTIFIER      = 'quoteIdentifier';
    public const FUNCTION_QUOTE_VALUE           = 'quoteValue';

    public const VALUE_QUOTE_SEPARATOR = 'quoteSeparator';

    /**
     * @return Driver\DriverInterface
     */
    public function getDriver();

    /**
     * @return Platform\PlatformInterface
     */
    public function getPlatform();
}
