<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\SqlInterface;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversClass(Platform::class)]
final class PlatformTest extends TestCase
{
    #[Test]
    public function constructorAcceptsTheAdapterPlatformForCompatibility(): void
    {
        $platform = new Platform(new TestAsset\TrustingSql92Platform());

        static::assertSame([], $platform->getDecorators());
    }

    #[Test]
    public function getDecoratorsIsEmptyForStandardSql(): void
    {
        static::assertSame([], (new Platform())->getDecorators());
    }

    #[Test]
    public function getSqlStringRendersTheSubjectUnchanged(): void
    {
        $adapterPlatform = new TestAsset\TrustingSql92Platform();
        $platform        = new Platform($adapterPlatform);
        $platform->setSubject(new Select('foo'));

        $sql = $platform->getSqlString($adapterPlatform);

        static::assertStringContainsString('SELECT', $sql);
        static::assertStringContainsString('"foo"', $sql);
    }

    #[Test]
    public function getSqlStringThrowsWhenSubjectIsNotSqlInterface(): void
    {
        $platform = new Platform(new TestAsset\TrustingSql92Platform());
        $platform->setSubject($this->createMock(PreparableSqlInterface::class));

        self::expectException(RuntimeException::class);
        $platform->getSqlString(new TestAsset\TrustingSql92Platform());
    }

    #[Test]
    public function prepareStatementRendersTheSubject(): void
    {
        $platform = new Platform(new TestAsset\TrustingSql92Platform());

        $select = new Select('foo');
        $select->where(['id' => 1]);
        $platform->setSubject($select);

        $statement = new StatementContainer();
        $result    = $platform->prepareStatement($this->createAdapter($platform), $statement);

        static::assertSame($statement, $result);
        static::assertStringContainsString('SELECT', $statement->getSql());
    }

    #[Test]
    public function prepareStatementThrowsWhenSubjectIsNotPreparable(): void
    {
        $platform = new Platform(new TestAsset\TrustingSql92Platform());
        $platform->setSubject($this->createMock(SqlInterface::class));

        self::expectException(RuntimeException::class);
        $platform->prepareStatement($this->createAdapter($platform), new StatementContainer());
    }

    private function createAdapter(Platform $platform): AdapterInterface
    {
        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('quoteIdentifier')->willReturnCallback(static fn($v) => "\"{$v}\"");
        $mockPlatform->method('quoteIdentifierInFragment')->willReturnCallback(static fn($v) => "\"{$v}\"");
        $mockPlatform->method('getIdentifierSeparator')->willReturn('.');
        $mockPlatform->method('getSqlPlatformDecorator')->willReturn($platform);

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->method('formatParameterName')->willReturn('?');

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getPlatform')->willReturn($mockPlatform);
        $adapter->method('getDriver')->willReturn($mockDriver);

        return $adapter;
    }
}
