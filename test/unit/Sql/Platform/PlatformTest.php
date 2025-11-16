<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

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
        $platform = new Platform($adapter);

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
        $platform = new Platform($this->resolveAdapter('sql92'));

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatformName');

        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionMethod->setAccessible(true);

        self::assertEquals('mysql', $reflectionMethod->invoke($platform, new TestAsset\TrustingMysqlPlatform()));
        self::assertEquals('sqlserver', $reflectionMethod->invoke(
            $platform,
            new TestAsset\TrustingSqlServerPlatform()
        ));
        self::assertEquals('oracle', $reflectionMethod->invoke($platform, new TestAsset\TrustingOraclePlatform()));
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
        $platform = null;

        switch ($platformName) {
            case 'sql92':
                $platform = new TestAsset\TrustingSql92Platform();
                break;
            case 'MySql':
                $platform = new TestAsset\TrustingMysqlPlatform();
                break;
            case 'Oracle':
                $platform = new TestAsset\TrustingOraclePlatform();
                break;
            case 'SqlServer':
                $platform = new TestAsset\TrustingSqlServerPlatform();
                break;
        }

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
