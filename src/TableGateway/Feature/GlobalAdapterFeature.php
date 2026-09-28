<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\Exception;

/**
 * @api
 */
class GlobalAdapterFeature extends AbstractFeature
{
    /** @var AdapterInterface[] */
    protected static array $staticAdapters = [];

    /**
     * Get static adapter
     *
     * @throws Exception\RuntimeException
     */
    public static function getStaticAdapter(): AdapterInterface
    {
        $class = static::class;

        $adapter = static::$staticAdapters[$class] ?? static::$staticAdapters[self::class] ?? null;

        if (! $adapter instanceof AdapterInterface) {
            throw new Exception\RuntimeException('No database adapter was found in the static registry.');
        }

        return $adapter;
    }

    /**
     * Set static adapter
     */
    public static function setStaticAdapter(AdapterInterface $adapter): void
    {
        $class = static::class;

        static::$staticAdapters[$class] = $adapter;
        if (self::class === $class) {
            static::$staticAdapters[self::class] = $adapter;
        }
    }

    /**
     * after initialization, retrieve the original adapter as "master"
     */
    public function preInitialize(): void
    {
        $this->tableGateway->adapter = self::getStaticAdapter();
    }
}
