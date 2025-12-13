<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Insert;

class Replace extends Insert
{
    public const SPECIFICATION_INSERT = 'replace';

    /** @var array<string, string> */
    protected array $specifications = [
        self::SPECIFICATION_INSERT => 'REPLACE INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'REPLACE INTO %1$s %2$s %3$s',
    ];

    protected function processreplace(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?string {
        return parent::processInsert($platform, $driver, $parameterContainer);
    }
}
