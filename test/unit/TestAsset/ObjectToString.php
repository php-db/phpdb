<?php

namespace PhpDbTest\TestAsset;

use Override;
use Stringable;

class ObjectToString implements Stringable
{
    public function __construct(protected string $value)
    {
    }

    #[Override] public function __toString(): string
    {
        return $this->value;
    }
}
