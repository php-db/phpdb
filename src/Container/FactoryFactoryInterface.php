<?php

declare(strict_types=1);

namespace PhpDb\Container;

interface FactoryFactoryInterface
{
    public function __invoke(): callable;
}
