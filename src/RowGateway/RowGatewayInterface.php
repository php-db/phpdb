<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

use PhpDb\ResultSet\RowPrototypeInterface;

/**
 * @api
 */
interface RowGatewayInterface extends RowPrototypeInterface
{
    public function delete(): int;

    public function save(): int;
}
