<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\TestAsset;

use Override;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderTrait;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;

/**
 * A driver that composes DriverFeatureProviderTrait without declaring
 * DriverFeatureProviderInterface.
 */
final class TestNonProviderFeatureDriver implements DriverInterface
{
    use DriverFeatureProviderTrait;

    #[Override]
    public function checkEnvironment(): bool
    {
        return true;
    }

    #[Override]
    public function createResult(mixed $resource): ResultInterface
    {
        /** @phpstan-ignore return.type */
        return null;
    }

    #[Override]
    public function createStatement(mixed $sqlOrResource = null): StatementInterface
    {
        /** @phpstan-ignore return.type */
        return null;
    }

    #[Override]
    public function formatParameterName(string $name, ?string $type = null): string
    {
        return '?';
    }

    #[Override]
    public function getConnection(): ConnectionInterface
    {
        /** @phpstan-ignore return.type */
        return null;
    }

    #[Override]
    public function getLastGeneratedValue(): string|int|false|null
    {
        return null;
    }

    #[Override]
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }
}
