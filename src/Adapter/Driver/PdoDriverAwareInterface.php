<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver;

interface PdoDriverAwareInterface
{
    /** Provides a fluent interface - implementation must return $this */
    public function setDriver(PdoDriverInterface $driver): static;
}
