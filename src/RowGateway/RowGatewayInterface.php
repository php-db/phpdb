<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

use PhpDb\ResultSet\RowPrototypeInterface;

interface RowGatewayInterface extends RowPrototypeInterface
{
    public function save(): int;

    public function delete(): int;
}
