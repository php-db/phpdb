<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;

/**
 * Test asset class used only by {@see \PhpDbTest\Adapter\Driver\Pdo\ConnectionTransactionsTest}
 */
final class ConnectionWrapper extends AbstractPdoConnection
{
    public function __construct(
        $connectionParameters = new PdoStubDriver()
    ) {
        parent::__construct($connectionParameters);
    }

    public function connect(): ConnectionInterface
    {
        return $this;
    }

    public function getCurrentSchema(): string
    {
        return 'test_schema';
    }

    public function getLastGeneratedValue(mixed $name = null): int|string|bool|null
    {
        return null;
    }

    public function getNestedTransactionsCount(): int
    {
        return $this->nestedTransactionsCount;
    }
}
