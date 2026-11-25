<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

/**
 * @api
 */
interface DriverAwareInterface
{
    /** Implementation should provide a fluent interface */
    public function setDriver(DriverInterface $driver): self;
}
