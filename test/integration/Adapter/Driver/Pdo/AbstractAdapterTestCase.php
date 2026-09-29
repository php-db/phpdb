<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Driver\Pdo;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getmypid;
use function shell_exec;

#[CoversMethod(AdapterInterface::class, 'getDriver')]
#[CoversMethod(AdapterInterface::class, 'getPlatform')]
abstract class AbstractAdapterTestCase extends TestCase
{
    use AdapterTrait;

    public ?int $port = null;

    #[Test]
    public function connection(): void
    {
        static::assertInstanceOf(AdapterInterface::class, $this->adapter);
    }

    #[Test]
    public function driverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        $this->getAdapter()->getDriver()->getConnection()->connect();

        static::assertTrue($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            static::assertTrue($this->isConnectedTcp());
        }

        $this->getAdapter()->getDriver()->getConnection()->disconnect();

        static::assertFalse($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            static::assertFalse($this->isConnectedTcp());
        }

        $this->getAdapter()->getDriver()->getConnection()->connect();

        static::assertTrue($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            static::assertTrue($this->isConnectedTcp());
        }

        $this->getAdapter()->getPlatform()->quoteValue('test');

        $this->getAdapter()->getDriver()->getConnection()->disconnect();

        static::assertFalse($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            static::assertFalse($this->isConnectedTcp());
        }
    }

    protected function isConnectedTcp(): bool
    {
        $mypid  = getmypid();
        $dbPort = (string) $this->port;
        /** @psalm-suppress ForbiddenCode - running lsof */
        $lsof = shell_exec("lsof -i -P -n | grep {$dbPort} | grep {$mypid}");

        return null !== $lsof;
    }

    protected function isTcpConnection(): bool
    {
        return $this->getHostname() !== 'localhost';
    }
}
