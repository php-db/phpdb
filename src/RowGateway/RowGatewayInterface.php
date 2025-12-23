<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

interface RowGatewayInterface
{
    public function save(): int;

    public function delete(): int;
}
