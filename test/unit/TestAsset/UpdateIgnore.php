<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Update;

/**
 * @psalm-return UpdateIgnore&static
 */
final class UpdateIgnore extends Update
{
    /**
     * Override specification update for testing
     *
     * @psalm-suppress InvalidClassConstantType
     */
    public const SPECIFICATION_UPDATE = 'updateIgnore';

    /** @var array<string, string> */
    protected $specifications = [
        self::SPECIFICATION_UPDATE => 'UPDATE IGNORE %1$s',
        self::SPECIFICATION_SET    => 'SET %1$s',
        self::SPECIFICATION_WHERE  => 'WHERE %1$s',
    ];

    protected function processupdateIgnore(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return parent::processUpdate($platform, $driver, $parameterContainer);
    }
}
