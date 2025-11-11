<?php

namespace PhpDb\Adapter;

/**
 * @property Driver\DriverInterface $driver
 * @property Platform\PlatformInterface $platform
 */
interface AdapterInterface
{
    /**
     * @return Driver\DriverInterface
     */
    public function getDriver();

    /**
     * @return Platform\PlatformInterface
     */
    public function getPlatform();
}
