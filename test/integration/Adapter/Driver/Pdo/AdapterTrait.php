<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Driver\Pdo;

use PhpDb\Adapter\AdapterInterface;

trait AdapterTrait
{
    protected ?AdapterInterface $adapter  = null;
    protected ?string           $hostname = 'localhost';

    public function getAdapter(): AdapterInterface
    {
        if (null === $this->adapter) {
            $this->fail('Adapter not initialized');
        }

        return $this->adapter;
    }

    protected function getHostname(): ?string
    {
        return $this->hostname;
    }
}
