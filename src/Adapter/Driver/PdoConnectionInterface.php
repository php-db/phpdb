<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

/**
 * @api
 */
interface PdoConnectionInterface extends ConnectionInterface
{
    public function getDsn(): string;
}
