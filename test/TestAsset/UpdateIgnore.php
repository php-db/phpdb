<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Update;

/**
 * Test asset for UPDATE IGNORE functionality.
 * This is a workaround since Update is final - duplicates Update behavior but with IGNORE keyword.
 *
 * @deprecated This test asset is for deprecated tests that require processUpdate method
 */
final class UpdateIgnore extends Update
{
}