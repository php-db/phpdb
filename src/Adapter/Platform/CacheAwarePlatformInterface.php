<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Psr\SimpleCache\CacheInterface;

interface CacheAwarePlatformInterface
{
    /**
     * Set the cache instance
     */
    public function setCache(?CacheInterface $cache): void;

    /**
     * Get the cache instance
     */
    public function getCache(): ?CacheInterface;

    /**
     * Get the cache TTL in seconds
     */
    public function getCacheTtl(): ?int;

    /**
     * Set the cache TTL in seconds
     */
    public function setCacheTtl(?int $ttl): void;
}
