<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\Sql92;

class TestSql92Platform extends Sql92
{
    public function __construct(
        protected ?DriverInterface $driver = null,
        protected bool $quoteIdentifiers = true
    ) {
    }
}
