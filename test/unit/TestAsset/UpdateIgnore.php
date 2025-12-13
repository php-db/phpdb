<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Update;

/**
 * @psalm-return UpdateIgnore&static
 */
class UpdateIgnore extends Update
{
    /**
     * Override specification update for testing
     *
     * @psalm-suppress InvalidClassConstantType
     */
    public const SPECIFICATION_UPDATE = 'updateIgnore';

    /** @var array<string, string> */
    protected array $specifications = [
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
