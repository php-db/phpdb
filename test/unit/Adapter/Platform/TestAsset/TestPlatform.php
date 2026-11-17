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
    /** @param array{string, string} $quoteIdentifier */
    public function __construct(
        protected ?DriverInterface $driver = null,
        protected array $quoteIdentifier = ['"', '"'],
    ) {}

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
