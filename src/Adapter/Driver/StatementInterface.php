<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

interface StatementInterface extends StatementContainerInterface
{
    /**
     * Get resource
     *
     * @return resource|false|null
     */
    public function getResource();

    /** Prepare sql */
    public function prepare(?string $sql = null): StatementInterface;

    /** Check if is prepared */
    public function isPrepared(): bool;

    /** Execute */
    public function execute(ParameterContainer|array|null $parameters = null): ?ResultInterface;
}
