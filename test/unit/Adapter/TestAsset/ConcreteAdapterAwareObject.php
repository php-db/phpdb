<?php

namespace PhpDbTest\Adapter\TestAsset;

use PhpDb\Adapter\AdapterAwareInterface;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\AdapterInterface;

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
