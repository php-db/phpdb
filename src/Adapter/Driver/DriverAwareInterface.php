<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver;

interface DriverAwareInterface
{
    /** Implementation should provide a fluent interface */
    public function setDriver(DriverInterface $driver): DriverAwareInterface;
}
