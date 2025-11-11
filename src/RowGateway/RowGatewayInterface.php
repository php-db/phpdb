<?php

namespace PhpDb\RowGateway;

interface RowGatewayInterface
{
    /**
     * @return int
     */
    public function save();

    /**
     * @return int
     */
    public function delete();
}
