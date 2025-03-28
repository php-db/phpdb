<?php

namespace LaminasTest\Db\TestAsset;

class ObjectToString implements \Stringable
{
    public function __construct(protected string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
