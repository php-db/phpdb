<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Psr\Container\ContainerInterface;

interface FactoryFactoryInterface
{
    public function __invoke(): callable;
}
