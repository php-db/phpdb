<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Adapter\Platform\PlatformInterface;

/**
 * @api
 *
 * The standard SQL platform: it registers no decorators, so every subject renders
 * itself. A driver whose dialect differs from standard SQL supplies its own platform.
 */
class Platform extends AbstractPlatform
{
    /**
     * @param PlatformInterface|null $platform Unused. Drivers used to hand their adapter platform
     *                                          over so it could be named and looked up; the dialect
     *                                          is now fixed by the platform object itself.
     */
    // @mago-expect analysis:unused-parameter
    public function __construct(?PlatformInterface $platform = null) {}
}
