<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\SqlInterface;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(Platform::class, '__construct')]
#[CoversMethod(Platform::class, 'setTypeDecorator')]
#[CoversMethod(Platform::class, 'getTypeDecorator')]
#[CoversMethod(Platform::class, 'getDecorators')]
#[CoversMethod(Platform::class, 'prepareStatement')]
#[CoversMethod(Platform::class, 'getSqlString')]
#[CoversMethod(Platform::class, 'resolvePlatformName')]
#[CoversMethod(Platform::class, 'resolvePlatform')]
#[CoversMethod(Platform::class, 'getDefaultPlatform')]
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

        self::assertEquals($adapter->getPlatform(), $reflectionMethod->invoke($platform, null));
    }

    /**
     * @throws ReflectionException
     */
    public function testResolvePlatformName(): void
    {
        $platform = new Platform($this->resolveAdapter('sql92')->getPlatform());

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatformName');

        self::assertEquals('mysql', $reflectionMethod->invoke($platform, new TestAsset\TrustingMysqlPlatform()));
        self::assertEquals('sqlserver', $reflectionMethod->invoke(
            $platform,
            new TestAsset\TrustingSqlServerPlatform()
        ));
        self::assertEquals('oracle', $reflectionMethod->invoke($platform, new TestAsset\TrustingOraclePlatform()));
        self::assertEquals('sql92', $reflectionMethod->invoke($platform, new TestAsset\TrustingSql92Platform()));
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

        /** @var DriverInterface&MockObject $mockDriver */
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();

        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturn('?');
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturnCallback(fn(): StatementContainer => new StatementContainer());

        return new Adapter($mockDriver, $platform, new ResultSet());
    }

    public function testSetTypeDecoratorRegistersDecorator(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $platform->setTypeDecorator(Select::class, $decorator);

        $decorators = $platform->getDecorators();
        self::assertArrayHasKey(Select::class, $decorators);
        self::assertSame($decorator, $decorators[Select::class]);
    }

    public function testGetTypeDecoratorReturnsSubjectWhenNoDecoratorRegistered(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        self::assertSame($select, $result);
    }

    public function testGetDefaultPlatformReturnsInstance(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $reflectionMethod = new ReflectionMethod($platform, 'getDefaultPlatform');
        $result           = $reflectionMethod->invoke($platform);

        self::assertSame($adapterPlatform, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolvePlatformNameCachesResult(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatformName');

        $first  = $reflectionMethod->invoke($platform, null);
        $second = $reflectionMethod->invoke($platform, null);

        self::assertEquals($first, $second);
        self::assertEquals('sql92', $first);
    }

    public function testResolvePlatformWithAdapterInterface(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('getName')->willReturn('TestPlatform');

        $mockAdapter = $this->createMock(AdapterInterface::class);
        $mockAdapter->expects($this->once())->method('getPlatform')->willReturn($mockPlatform);

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatform');
        $result           = $reflectionMethod->invoke($platform, $mockAdapter);

        self::assertSame($mockPlatform, $result);
    }

    public function testResolvePlatformWithPlatformInterface(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $mockPlatform = $this->createMock(PlatformInterface::class);

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatform');
        $result           = $reflectionMethod->invoke($platform, $mockPlatform);

        self::assertSame($mockPlatform, $result);
    }

    public function testPrepareStatementThrowsWhenSubjectNotPreparable(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $subject = $this->createMock(SqlInterface::class);
        $platform->setSubject($subject);

        $adapter   = $this->resolveAdapter('sql92');
        $statement = new StatementContainer();

        $this->expectException(RuntimeException::class);
        $platform->prepareStatement($adapter, $statement);
    }

    public function testGetSqlStringThrowsWhenSubjectNotSqlInterface(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $subject = $this->createMock(PreparableSqlInterface::class);
        $platform->setSubject($subject);

        $this->expectException(RuntimeException::class);
        $platform->getSqlString($adapterPlatform);
    }

    public function testGetSqlStringDelegatesToTypeDecorator(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $select = new Select('foo');
        $platform->setSubject($select);

        $sql = $platform->getSqlString($adapterPlatform);

        self::assertStringContainsString('SELECT', $sql);
        self::assertStringContainsString('"foo"', $sql);
    }

    public function testGetTypeDecoratorMatchesExactClass(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $decorator->expects(self::once())->method('setSubject');
        $platform->setTypeDecorator(Select::class, $decorator);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        self::assertSame($decorator, $result);
    }

    public function testGetTypeDecoratorFallsThroughWhenNoMatch(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $platform->setTypeDecorator(Insert::class, $decorator);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        self::assertSame($select, $result);
    }

    public function testGetTypeDecoratorMatchesByInstanceofLoop(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $innerPlatform = new AbstractPlatform();
        $platform->setTypeDecorator(SqlInterface::class, $innerPlatform);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        self::assertSame($innerPlatform, $result);
    }
}
