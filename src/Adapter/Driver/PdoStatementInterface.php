<?php

namespace Laminas\Db\Adapter\Driver;

use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\StatementContainerInterface;

interface PdoStatementInterface
{
    public function setDriver(PdoDriverInterface $driver): static;
}
