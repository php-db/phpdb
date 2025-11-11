<?php

namespace PhpDb\Adapter\Driver;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

interface StatementInterface extends StatementContainerInterface
{
    /**
     * Get resource
     *
     * @return resource
     */
    public function getResource();

    /**
     * Prepare sql
     *
     * @param string $sql
     */
    public function prepare($sql = null);

    /**
     * Check if is prepared
     *
     * @return bool
     */
    public function isPrepared();

    /**
     * Execute
     *
     * @param null|array|ParameterContainer $parameters
     * @return ResultInterface
     */
    public function execute($parameters = null);
}
