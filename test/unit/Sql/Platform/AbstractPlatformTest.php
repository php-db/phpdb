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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

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

    #[Test]
    public function getSqlStringDelegatesToDecoratorSubject(): void
    {
        $select = new Select('foo');
        $this->platform->setSubject($select);

        $sql = $this->platform->getSqlString();

        static::assertStringContainsString('SELECT', $sql);
        static::assertStringContainsString('"foo"', $sql);
    }

    #[Test]
    public function getSqlStringThrowsWhenSubjectNotSqlInterface(): void
    {
        $subject = $this->createMock(PreparableSqlInterface::class);
        $this->platform->setSubject($subject);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::SUBJECT_NOT_IMPLEMENTING,
            SqlInterface::class,
            'getSqlString()',
        ));
        $this->platform->getSqlString();
    }

    #[Test]
    public function getTypeDecoratorLoopMatchesByInstanceof(): void
    {
        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $decorator->method('setSubject')->willReturnSelf();

        $this->platform->setTypeDecorator(Select::class, $decorator);

        $subject = new Select('foo');
        $result  = $this->platform->getTypeDecorator($subject);

        static::assertSame($decorator, $result);
    }

    #[Test]
    public function getTypeDecoratorReturnsSubjectWhenNoMatch(): void
    {
        $subject = $this->createMock(SqlInterface::class);

        $result = $this->platform->getTypeDecorator($subject);

        static::assertSame($subject, $result);
    }

    #[Test]
    public function prepareStatementDelegatesToDecoratorSubject(): void
    {
        $select = new Select('foo');
        $select->where(['id' => 1]);
        $this->platform->setSubject($select);

        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('quoteIdentifier')->willReturnCallback(static fn($v) => '"' . $v . '"');
        $mockPlatform->method('quoteIdentifierInFragment')->willReturnCallback(static fn($v) => '"' . $v . '"');
        $mockPlatform->method('getIdentifierSeparator')->willReturn('.');
        $mockPlatform->method('getSqlPlatformDecorator')->willReturn($this->platform);

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->method('formatParameterName')->willReturn('?');

        $adapter = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $adapter->method('getPlatform')->willReturn($mockPlatform);
        $adapter->method('getDriver')->willReturn($mockDriver);

        $statement = new StatementContainer();
        $result    = $this->platform->prepareStatement($adapter, $statement);

        static::assertSame($statement, $result);
        static::assertStringContainsString('SELECT', $statement->getSql());
    }

    #[Test]
    public function prepareStatementThrowsWhenSubjectNotPreparable(): void
    {
        $subject = $this->createMock(SqlInterface::class);
        $this->platform->setSubject($subject);

        $adapter   = $this->createMock(AdapterInterface::class);
        $statement = new StatementContainer();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::SUBJECT_NOT_IMPLEMENTING,
            PreparableSqlInterface::class,
            'prepareStatement()',
        ));
        $this->platform->prepareStatement($adapter, $statement);
    }

    #[Test]
    public function setAndGetTypeDecorator(): void
    {
        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $this->platform->setTypeDecorator(Select::class, $decorator);

        $decorators = $this->platform->getDecorators();

        static::assertArrayHasKey(Select::class, $decorators);
        static::assertSame($decorator, $decorators[Select::class]);
    }

    #[Test]
    public function setSubjectReturnsStatic(): void
    {
        $subject = $this->createMock(SqlInterface::class);
        $result  = $this->platform->setSubject($subject);

        static::assertSame($this->platform, $result);
    }

    protected function setUp(): void
    {
        $this->platform = new AbstractPlatform();
    }
}
