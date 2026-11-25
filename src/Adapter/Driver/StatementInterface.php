<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

/**
 * @api
 *
 * @template TStatement of object|resource = object|resource The native prepared statement handle
 */
interface StatementInterface extends StatementContainerInterface
{
    /**
     * Execute
     *
     * @param ParameterContainer|array<array-key, mixed>|null $parameters
     */
    public function execute(ParameterContainer|array|null $parameters = null): ?ResultInterface;

    /**
     * Get resource
     *
     * @return TStatement|false|null
     */
    public function getResource(): mixed;

    /** Check if is prepared */
    public function isPrepared(): bool;

    /** Prepare sql */
    public function prepare(?string $sql = null): self;
}
