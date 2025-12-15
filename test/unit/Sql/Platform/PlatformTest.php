<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use InvalidArgumentException;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Platform\Platform;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

class PlatformTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testResolveDefaultPlatform(): void
    {
        $adapter  = $this->resolveAdapter('sql92');
        $platform = new Platform($adapter->getPlatform());

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatform');

        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionMethod->setAccessible(true);

        self::assertEquals($adapter->getPlatform(), $reflectionMethod->invoke($platform, null));
    }

    /**
     * @throws ReflectionException
     */
    public function testResolvePlatformName(): void
    {
        $platform = new Platform($this->resolveAdapter('sql92')->getPlatform());

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatformName');

        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionMethod->setAccessible(true);

        self::assertEquals('sql92', $reflectionMethod->invoke($platform, new TestAsset\TrustingSql92Platform()));
    }

    #[Group('6890')]
    public function testAbstractPlatformCrashesGracefullyOnMissingDefaultPlatform(): void
    {
        $this->markTestSkipped(
            'Cannot modify readonly properties in Adapter - test is incompatible with readonly properties'
        );
    }

    #[Group('6890')]
    public function testAbstractPlatformCrashesGracefullyOnMissingDefaultPlatformWithGetDecorators(): void
    {
        $this->markTestSkipped(
            'Cannot modify readonly properties in Adapter - test is incompatible with readonly properties'
        );
    }

    protected function resolveAdapter(string $platformName): Adapter
    {
        $platform = match ($platformName) {
            'sql92' => new TestAsset\TrustingSql92Platform(),
            default => throw new InvalidArgumentException("Unknown platform: $platformName"),
        };

        /** @var DriverInterface|MockObject $mockDriver */
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();

        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturn('?');
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturnCallback(fn(): StatementContainer => new StatementContainer());

        return new Adapter($mockDriver, $platform, new ResultSet());
    }
}
