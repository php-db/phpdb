<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Adapter\SchemaAwareInterface;

interface ConnectionInterface extends SchemaAwareInterface
{
    public function beginTransaction(): self;

    public function commit(): self;

    public function connect(): self;

    public function disconnect(): self;

    public function execute(string $sql): ?ResultInterface;

    public function getConnectionParameters(): array;

    /**
     * Get last generated id
     *
     * $name Ignored (this is not ignored for PDO), imagine that...
     */
    public function getLastGeneratedValue(?string $name = null): string|int|false|null;

    /**
     * Get resource
     *
     * @return resource|object|null
     */
    public function getResource();

    /** Checks whether the connection is in transaction state. */
    public function inTransaction(): bool;

    public function isConnected(): bool;

    public function rollback(): self;

    public function setConnectionParameters(array $connectionParameters): self;
}
