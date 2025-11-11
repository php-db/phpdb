<?php

namespace PhpDbTest;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;

/**
 * Helper trait for creating properly mocked Adapter instances in tests
 */
trait AdapterTestTrait
{
    /**
     * Creates a mock Adapter with all required dependencies
     *
     * @param DriverInterface|null $driver Optional mock driver, will create one if not provided
     * @param PlatformInterface|null $platform Optional mock platform, will create Sql92 if not provided
     * @param ResultSetInterface|null $resultSet Optional mock result set, will create one if not provided
     */
    protected function createMockAdapter(
        ?DriverInterface $driver = null,
        ?PlatformInterface $platform = null,
        ?ResultSetInterface $resultSet = null
    ): Adapter {
        $driver    = $driver ?? $this->createMock(DriverInterface::class);
        $platform  = $platform ?? new Sql92();
        $resultSet = $resultSet ?? new ResultSet();

        return $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$driver, $platform, $resultSet])
            ->getMock();
    }

    /**
     * Creates a real Adapter instance (not mocked) with all required dependencies
     *
     * @param DriverInterface|null $driver Optional driver, will create mock if not provided
     * @param PlatformInterface|null $platform Optional platform, will create Sql92 if not provided
     * @param ResultSetInterface|null $resultSet Optional result set, will create one if not provided
     */
    protected function createAdapter(
        ?DriverInterface $driver = null,
        ?PlatformInterface $platform = null,
        ?ResultSetInterface $resultSet = null
    ): Adapter {
        $driver    = $driver ?? $this->createMock(DriverInterface::class);
        $platform  = $platform ?? new Sql92();
        $resultSet = $resultSet ?? new ResultSet();

        return new Adapter($driver, $platform, $resultSet);
    }
}
