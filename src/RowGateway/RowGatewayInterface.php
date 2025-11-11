<?php

namespace PhpDb\RowGateway;

interface RowGatewayInterface
{
    public function save();

    public function delete();
}
