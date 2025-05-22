<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver;

interface PdoDriverAwareInterface
{
    /** Implementation should provide a fluent interface */
    public function setDriver(PdoDriverInterface $driver): static;
}
