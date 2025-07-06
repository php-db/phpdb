<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use PhpDb\Adapter\SchemaAwareInterface;

interface ConnectionInterface extends SchemaAwareInterface
{
    public function beginTransaction(): ConnectionInterface;

    public function connect(): ConnectionInterface;

    public function commit(): ConnectionInterface;

    public function disconnect(): ConnectionInterface;

    public function execute(string $sql): ?ResultInterface;

    public function getConnectionParameters(): array;

    /**
     * Get last generated id
     *
     * @param null $name Ignored (this is not ignored for PDO), imagine that...
     *
     * todo: narrow this to string|int|bool|null
     * until version bumps to PHP 8.2 minimum then narrow to string|int|false
     */
    public function getLastGeneratedValue($name = null): string|int|bool|null;

    /**
     * Get resource
     *
     * @return resource
     */
    public function getResource();

    /** Checks whether the connection is in transaction state. */
    public function inTransaction(): bool;

    public function isConnected(): bool;

    public function rollback(): ConnectionInterface;

    public function setConnectionParameters(array $connectionParameters): ConnectionInterface;
}
