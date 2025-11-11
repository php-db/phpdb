<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

use function getmypid;
use function shell_exec;

#[CoversMethod(AdapterInterface::class, '__construct()')]
abstract class AbstractAdapterTestCase extends TestCase
{
    use AdapterTrait;

    public ?int $port = null;

    public function testConnection(): void
    {
        $this->assertInstanceOf(AdapterInterface::class, $this->adapter);
    }

    public function testDriverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        $this->getAdapter()->getDriver()->getConnection()->connect();

        self::assertTrue($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($this->isConnectedTcp());
        }

        $this->getAdapter()->getDriver()->getConnection()->disconnect();

        self::assertFalse($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($this->isConnectedTcp());
        }

        $this->getAdapter()->getDriver()->getConnection()->connect();

        self::assertTrue($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($this->isConnectedTcp());
        }

        $this->getAdapter()->getPlatform()->quoteValue('test');

        $this->getAdapter()->getDriver()->getConnection()->disconnect();

        self::assertFalse($this->getAdapter()->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($this->isConnectedTcp());
        }
    }

    protected function isConnectedTcp(): bool
    {
        $mypid  = getmypid();
        $dbPort = (string) $this->port;
        /** @psalm-suppress ForbiddenCode - running lsof */
        $lsof = shell_exec("lsof -i -P -n | grep $dbPort | grep $mypid");

        return $lsof !== null;
    }

    protected function isTcpConnection(): bool
    {
        return $this->getHostname() !== 'localhost';
    }
}
