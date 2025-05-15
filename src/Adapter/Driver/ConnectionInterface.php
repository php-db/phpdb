<?php

namespace Laminas\Db\Adapter\Driver;

/**
 *
 * @property $driver
 * @property $resource
 */
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
    public function connect(): static;

    /** Is connected */
    public function isConnected(): bool;

    /** Disconnect */
    public function disconnect(): static;

    /** Begin transaction */
    public function beginTransaction(): static;

    /** Commit */
    public function commit(): static;

    /** Rollback */
    public function rollback(): static;

    /** Execute */
    public function execute(string $sql): ResultInterface;

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
