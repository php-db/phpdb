<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\TestAsset;

use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\ResultInterface;

final class TestConnection extends AbstractConnection
{
    protected ?string $driverName = null;

    public function beginTransaction(): ConnectionInterface
    {
        return $this;
    }

    public function commit(): ConnectionInterface
    {
        return $this;
    }

    public function connect(): ConnectionInterface
    {
        $this->resource = 'fake-resource';

        return $this;
    }

    public function execute(string $sql): ?ResultInterface
    {
        return null;
    }

    public function getCurrentSchema(): string|false
    {
        return false;
    }

    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        return false;
    }

    public function isConnected(): bool
    {
        return $this->resource !== null;
    }

    public function rollback(): ConnectionInterface
    {
        return $this;
    }
}
