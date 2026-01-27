<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Psr\SimpleCache\CacheInterface;

trait CacheAwarePlatformTrait
{
    protected ?CacheInterface $cache = null;

    protected ?int $cacheTtl = null;

    public function setCache(?CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    public function setCacheTtl(?int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }
}
