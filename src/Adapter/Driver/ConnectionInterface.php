<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver;

interface ConnectionInterface
{
    /**
     * Get current schema
     *
     * todo: narrow this to string|false when version bumps to PHP 8.2 minimum
     */
    public function getCurrentSchema(): string|bool;

    /**
     * Get resource
     *
     * @return resource
     */
    public function getResource();

    /** Connect */
    public function connect(): ConnectionInterface;

    /** Is connected */
    public function isConnected(): bool;

    /** Disconnect */
    public function disconnect(): ConnectionInterface;

    /** Begin transaction */
    public function beginTransaction(): ConnectionInterface;

    /** Commit */
    public function commit(): ConnectionInterface;

    /** Rollback */
    public function rollback(): ConnectionInterface;

    /** Execute */
    public function execute(string $sql): ?ResultInterface;

    /**
     * Get last generated id
     *
     * @param null $name Ignored (this is not ignored for PDO), imagine that...
     *
     * todo: narrow this to string|int|bool
     * until version bumps to PHP 8.2 minimum then narrow to string|int|false
     */
    public function getLastGeneratedValue($name = null): string|int|bool|null;
}
