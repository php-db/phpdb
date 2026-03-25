<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Platform\TestAsset;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

final class TestPlatform extends AbstractPlatform
{
    public function __construct(
        protected ?DriverInterface $driver = null,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return 'Test';
    }

    #[Override]
    public function getSqlPlatformDecorator(): PlatformDecoratorInterface
    {
        return new Platform($this);
    }
}
