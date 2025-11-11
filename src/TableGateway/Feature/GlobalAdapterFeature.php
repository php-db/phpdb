<?php

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\Adapter;
use PhpDb\TableGateway\Exception;

class GlobalAdapterFeature extends AbstractFeature
{
    /** @var Adapter[] */
    protected static $staticAdapters = [];

    /**
     * Set static adapter
     */
    public static function setStaticAdapter(Adapter $adapter): void
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
     * @return Adapter
     */
    public static function getStaticAdapter()
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
