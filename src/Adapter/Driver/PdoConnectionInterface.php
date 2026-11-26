<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PDO;

/**
 * @api
 *
 * @extends ConnectionInterface<PDO>
 */
interface PdoConnectionInterface extends ConnectionInterface
{
    public function getDsn(): string;
}
