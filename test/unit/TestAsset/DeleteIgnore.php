<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Delete;

class DeleteIgnore extends Delete
{
    public const SPECIFICATION_DELETE = 'deleteIgnore';

    /** @var array<string, string> */
    protected array $specifications = [
        self::SPECIFICATION_DELETE => 'DELETE IGNORE FROM %1$s',
        self::SPECIFICATION_WHERE  => 'WHERE %1$s',
    ];

    protected function processdeleteIgnore(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return parent::processDelete($platform, $driver, $parameterContainer);
    }
}
