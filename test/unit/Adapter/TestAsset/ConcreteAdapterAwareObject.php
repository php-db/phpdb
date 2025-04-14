<?php

namespace LaminasTest\Db\Adapter\TestAsset;

use Laminas\Db\Adapter\AdapterAwareInterface;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Db\Adapter\AdapterInterface;

class ConcreteAdapterAwareObject implements AdapterAwareInterface
{
    use AdapterAwareTrait;

    public function __construct(private readonly array $options = [])
    {
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
