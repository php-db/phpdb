<?php

namespace Laminas\Db\Adapter\Driver;

use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\StatementContainerInterface;

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
     */
    public function prepare(string|null $sql = null): static|null;

    /**
     * Check if is prepared
     */
    public function isPrepared(): bool;

    /**
     * Execute
     */
    public function execute(null|array|ParameterContainer $parameters = null): ResultInterface|null;
}
