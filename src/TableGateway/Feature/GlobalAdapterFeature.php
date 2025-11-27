<?php

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\Exception;

class GlobalAdapterFeature extends AbstractFeature
{
    /** @var AdapterInterface[] */
    protected static array $staticAdapters = [];

    /**
     * Set static adapter
     */
    public static function setStaticAdapter(AdapterInterface $adapter): void
    {
        $class = static::class;

        static::$staticAdapters[$class] = $adapter;
        if ($class === self::class) {
            static::$staticAdapters[self::class] = $adapter;
        }
    }

    /**
     * Get static adapter
     *
     * @throws Exception\RuntimeException
     */
    public static function getStaticAdapter(): AdapterInterface
    {
        $class = static::class;

        // class specific adapter
        if (isset(static::$staticAdapters[$class])) {
            return static::$staticAdapters[$class];
        }

        // default adapter
        if (isset(static::$staticAdapters[self::class])) {
            return static::$staticAdapters[self::class];
        }

        throw new Exception\RuntimeException('No database adapter was found in the static registry.');
    }

    /**
     * after initialization, retrieve the original adapter as "master"
     */
    public function preInitialize(): void
    {
        $this->tableGateway->adapter = self::getStaticAdapter();
    }
}
