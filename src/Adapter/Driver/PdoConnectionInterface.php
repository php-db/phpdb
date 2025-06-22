<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver;

interface PdoConnectionInterface
{
    public function getDsn(): string;
}
