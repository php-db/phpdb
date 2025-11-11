<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo;

use PhpDb\Adapter\AdapterInterface;
use Override;

trait AdapterTrait
{
    protected ?AdapterInterface $adapter = null;
    protected ?string $hostname          = 'localhost';

    public function getAdapter(): AdapterInterface
    {
        if ($this->adapter === null) {
            $this->fail('Adapter not initialized');
        }

        return $this->adapter;
    }

    protected function getHostname(): ?string
    {
        return $this->hostname;
    }
}
