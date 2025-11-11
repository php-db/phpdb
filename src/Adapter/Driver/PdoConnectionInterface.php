<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

interface PdoConnectionInterface
{
    public function getDsn(): string;
}
