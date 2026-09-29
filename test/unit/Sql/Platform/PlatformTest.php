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
    #[\PHPUnit\Framework\Attributes\Test]
    public function getDefaultPlatformReturnsInstance(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $reflectionMethod = new ReflectionMethod($platform, 'getDefaultPlatform');
        $result           = $reflectionMethod->invoke($platform);

        static::assertSame($adapterPlatform, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringDelegatesToTypeDecorator(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $select = new Select('foo');
        $platform->setSubject($select);

        $sql = $platform->getSqlString($adapterPlatform);

        static::assertStringContainsString('SELECT', $sql);
        static::assertStringContainsString('"foo"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringThrowsWhenSubjectNotSqlInterface(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $subject = $this->createMock(PreparableSqlInterface::class);
        $platform->setSubject($subject);

        self::expectException(RuntimeException::class);
        $platform->getSqlString($adapterPlatform);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTypeDecoratorFallsThroughWhenNoMatch(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $platform->setTypeDecorator(Insert::class, $decorator);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        static::assertSame($select, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTypeDecoratorMatchesByInstanceofLoop(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $innerPlatform = new AbstractPlatform();
        $platform->setTypeDecorator(SqlInterface::class, $innerPlatform);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        static::assertSame($innerPlatform, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTypeDecoratorMatchesExactClass(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $decorator->expects(self::once())->method('setSubject');
        $platform->setTypeDecorator(Select::class, $decorator);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        static::assertSame($decorator, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTypeDecoratorReturnsSubjectWhenNoDecoratorRegistered(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $select = new Select('foo');
        $result = $platform->getTypeDecorator($select);

        static::assertSame($select, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function prepareStatementThrowsWhenSubjectNotPreparable(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $subject = $this->createMock(SqlInterface::class);
        $platform->setSubject($subject);

        $adapter   = $this->resolveAdapter('sql92');
        $statement = new StatementContainer();

        self::expectException(RuntimeException::class);
        $platform->prepareStatement($adapter, $statement);
    }

    /**
     * @throws ReflectionException
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function resolveDefaultPlatform(): void
    {
        $adapter  = $this->resolveAdapter('sql92');
        $platform = new Platform($adapter->getPlatform());

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatform');

        static::assertEquals($adapter->getPlatform(), $reflectionMethod->invoke($platform, null));
    }

    /**
     * @throws ReflectionException
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function resolvePlatformName(): void
    {
        $platform = new Platform($this->resolveAdapter('sql92')->getPlatform());

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatformName');

        static::assertSame('mysql', $reflectionMethod->invoke($platform, new TestAsset\TrustingMysqlPlatform()));
        static::assertSame('sqlserver', $reflectionMethod->invoke(
            $platform,
            new TestAsset\TrustingSqlServerPlatform(),
        ));
        static::assertSame('oracle', $reflectionMethod->invoke($platform, new TestAsset\TrustingOraclePlatform()));
        static::assertSame('sql92', $reflectionMethod->invoke($platform, new TestAsset\TrustingSql92Platform()));
    }

    /**
     * @throws ReflectionException
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function resolvePlatformNameCachesResult(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatformName');

        $first  = $reflectionMethod->invoke($platform, null);
        $second = $reflectionMethod->invoke($platform, null);

        static::assertEquals($first, $second);
        static::assertSame('sql92', $first);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolvePlatformWithAdapterInterface(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('getName')->willReturn('TestPlatform');

        $mockAdapter = $this->createMock(AdapterInterface::class);
        $mockAdapter->expects($this->once())->method('getPlatform')->willReturn($mockPlatform);

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatform');
        $result           = $reflectionMethod->invoke($platform, $mockAdapter);

        static::assertSame($mockPlatform, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolvePlatformWithPlatformInterface(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $mockPlatform = $this->createMock(PlatformInterface::class);

        $reflectionMethod = new ReflectionMethod($platform, 'resolvePlatform');
        $result           = $reflectionMethod->invoke($platform, $mockPlatform);

        static::assertSame($mockPlatform, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setTypeDecoratorRegistersDecorator(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);

        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $platform->setTypeDecorator(Select::class, $decorator);

        $decorators = $platform->getDecorators();
        static::assertArrayHasKey(Select::class, $decorators);
        static::assertSame($decorator, $decorators[Select::class]);
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
            ->willReturnCallback(static fn(): StatementContainer => new StatementContainer());

        return new Adapter($mockDriver, $platform, new ResultSet());
    }
}
