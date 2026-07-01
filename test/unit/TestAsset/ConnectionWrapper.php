<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PDO;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;

/**
 * Test asset class used only by {@see \PhpDbTest\Adapter\Driver\Pdo\ConnectionTransactionsTest}
 */
final class ConnectionWrapper extends AbstractPdoConnection
{
    public function __construct(
        PDO $connectionParameters = new PdoStubDriver()
    ) {
        $this->setResource($connectionParameters);
    }

    public function connect(): ConnectionInterface
    {
        return $this;
    }

    public function getCurrentSchema(): string
    {
        return 'test_schema';
    }

    public function getLastGeneratedValue(?string $name = null): string|int|false
    {
        return false;
    }

    public function getNestedTransactionsCount(): int
    {
        return $this->nestedTransactionsCount;
    }
}
