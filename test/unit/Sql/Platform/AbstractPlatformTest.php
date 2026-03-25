<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\SqlInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(AbstractPlatform::class, 'setSubject')]
#[CoversMethod(AbstractPlatform::class, 'setTypeDecorator')]
#[CoversMethod(AbstractPlatform::class, 'getTypeDecorator')]
#[CoversMethod(AbstractPlatform::class, 'getDecorators')]
#[CoversMethod(AbstractPlatform::class, 'prepareStatement')]
#[CoversMethod(AbstractPlatform::class, 'getSqlString')]
final class AbstractPlatformTest extends TestCase
{
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->platform = new AbstractPlatform();
    }

    public function testSetSubjectReturnsStatic(): void
    {
        $subject = $this->createMock(SqlInterface::class);
        $result  = $this->platform->setSubject($subject);

        self::assertSame($this->platform, $result);
    }

    public function testSetAndGetTypeDecorator(): void
    {
        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $this->platform->setTypeDecorator(Select::class, $decorator);

        $decorators = $this->platform->getDecorators();

        self::assertArrayHasKey(Select::class, $decorators);
        self::assertSame($decorator, $decorators[Select::class]);
    }

    public function testGetTypeDecoratorReturnsSubjectWhenNoMatch(): void
    {
        $subject = $this->createMock(SqlInterface::class);

        $result = $this->platform->getTypeDecorator($subject);

        self::assertSame($subject, $result);
    }

    public function testGetTypeDecoratorLoopMatchesByInstanceof(): void
    {
        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $decorator->method('setSubject')->willReturnSelf();

        $this->platform->setTypeDecorator(Select::class, $decorator);

        $subject = new Select('foo');
        $result  = $this->platform->getTypeDecorator($subject);

        self::assertSame($decorator, $result);
    }

    public function testPrepareStatementThrowsWhenSubjectNotPreparable(): void
    {
        $subject = $this->createMock(SqlInterface::class);
        $this->platform->setSubject($subject);

        $adapter   = $this->createMock(AdapterInterface::class);
        $statement = new StatementContainer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The subject does not appear to implement');
        $this->platform->prepareStatement($adapter, $statement);
    }

    public function testGetSqlStringThrowsWhenSubjectNotSqlInterface(): void
    {
        $subject = $this->createMock(PreparableSqlInterface::class);
        $this->platform->setSubject($subject);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The subject does not appear to implement');
        $this->platform->getSqlString();
    }

    public function testGetSqlStringDelegatesToDecoratorSubject(): void
    {
        $select = new Select('foo');
        $this->platform->setSubject($select);

        $sql = $this->platform->getSqlString();

        self::assertStringContainsString('SELECT', $sql);
        self::assertStringContainsString('"foo"', $sql);
    }

    public function testPrepareStatementDelegatesToDecoratorSubject(): void
    {
        $select = new Select('foo');
        $select->where(['id' => 1]);
        $this->platform->setSubject($select);

        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('quoteIdentifier')->willReturnCallback(fn($v) => '"' . $v . '"');
        $mockPlatform->method('quoteIdentifierInFragment')->willReturnCallback(fn($v) => '"' . $v . '"');
        $mockPlatform->method('getIdentifierSeparator')->willReturn('.');
        $mockPlatform->method('getSqlPlatformDecorator')->willReturn($this->platform);

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->method('formatParameterName')->willReturn('?');

        $adapter = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $adapter->method('getPlatform')->willReturn($mockPlatform);
        $adapter->method('getDriver')->willReturn($mockDriver);

        $statement = new StatementContainer();
        $result    = $this->platform->prepareStatement($adapter, $statement);

        self::assertSame($statement, $result);
        self::assertStringContainsString('SELECT', $statement->getSql());
    }
}
