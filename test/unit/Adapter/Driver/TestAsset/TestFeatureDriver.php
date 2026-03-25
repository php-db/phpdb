<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\TestAsset;

use Override;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderTrait;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;

class TestFeatureDriver implements DriverInterface, DriverFeatureProviderInterface
{
    use DriverFeatureProviderTrait;

    #[Override]
    public function getDatabasePlatformName(string $nameFormat = self::NAME_FORMAT_CAMELCASE): string
    {
        return 'test';
    }

    #[Override]
    public function checkEnvironment(): bool
    {
        return true;
    }

    #[Override]
    public function getConnection(): ConnectionInterface
    {
        /** @phpstan-ignore return.type */
        return null;
    }

    /** @param resource|string|null $sqlOrResource */
    #[Override]
    public function createStatement($sqlOrResource = null): StatementInterface
    {
        /** @phpstan-ignore return.type */
        return null;
    }

    /** @param resource $resource */
    #[Override]
    public function createResult($resource): ResultInterface
    {
        /** @phpstan-ignore return.type */
        return null;
    }

    #[Override]
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    #[Override]
    public function formatParameterName(string $name, ?string $type = null): string
    {
        return '?';
    }

    #[Override]
    public function getLastGeneratedValue(): string|int|false|null
    {
        return null;
    }
}
