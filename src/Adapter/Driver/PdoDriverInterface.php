<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PDO;
use PDOStatement;

/**
 * @api
 *
 * @extends DriverInterface<PDO, PDOStatement, PDOStatement>
 */
interface PdoDriverInterface extends DriverInterface {}
