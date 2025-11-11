<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

interface PdoDriverAwareInterface
{
    /** Implementation should provide a fluent interface */
    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface;
}
