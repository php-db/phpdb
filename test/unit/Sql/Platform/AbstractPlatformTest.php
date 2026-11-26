<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Adapter\StatementContainerInterface;
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
#[CoversMethod(AbstractPlatform::class, 'decoratorFor')]
#[CoversMethod(AbstractPlatform::class, 'getDecorators')]
#[CoversMethod(AbstractPlatform::class, 'prepareStatement')]
#[CoversMethod(AbstractPlatform::class, 'getSqlString')]
final class AbstractPlatformTest extends TestCase
{
    private AbstractPlatform $platform;

    #[Test]
    public function decoratorForBindsTheSubjectAndReturnsTheDecorator(): void
    {
        $decorator = $this->createRenderableDecorator();
        $this->platform->setTypeDecorator(Select::class, $decorator);

        $subject = new Select('foo');

        static::assertSame($decorator, $this->platform->decoratorFor($subject));
        static::assertSame($subject, $decorator->boundSubject);
    }

    #[Test]
    public function decoratorForReturnsNullWhenNoDecoratorIsRegisteredForTheType(): void
    {
        $this->platform->setTypeDecorator(Select::class, $this->createRenderableDecorator());

        static::assertNull($this->platform->decoratorFor($this->createMock(SqlInterface::class)));
    }

    #[Test]
    public function getDecoratorsReturnsTheRegisteredDecorators(): void
    {
        $decorator = $this->createRenderableDecorator();
        $this->platform->setTypeDecorator(Select::class, $decorator);

        static::assertSame([Select::class => $decorator], $this->platform->getDecorators());
    }

    #[Test]
    public function getSqlStringDelegatesToTheRegisteredDecorator(): void
    {
        $this->platform->setTypeDecorator(Select::class, $this->createRenderableDecorator());
        $this->platform->setSubject(new Select('foo'));

        static::assertSame('rendered by the decorator', $this->platform->getSqlString());
    }

    #[Test]
    public function getSqlStringFallsBackToTheSubjectWhenTheDecoratorCannotRender(): void
    {
        $this->platform->setTypeDecorator(PreparableSqlInterface::class, $this->createPrepareOnlyDecorator());
        $this->platform->setSubject(new Select('foo'));

        $sql = $this->platform->getSqlString();

        static::assertStringContainsString('SELECT', $sql);
        static::assertStringContainsString('"foo"', $sql);
    }

    #[Test]
    public function getSqlStringRendersTheSubjectWhenNoDecoratorIsRegistered(): void
    {
        $this->platform->setSubject(new Select('foo'));

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
    public function getSqlStringThrowsWhenSubjectWasCleared(): void
    {
        $this->platform->setSubject(new Select('foo'));
        $this->platform->setSubject(null);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::SUBJECT_NOT_IMPLEMENTING,
            SqlInterface::class,
            'getSqlString()',
        ));
        $this->platform->getSqlString();
    }

    #[Test]
    public function prepareStatementFallsBackToTheSubjectWhenTheDecoratorCannotPrepare(): void
    {
        $this->platform->setTypeDecorator(SqlInterface::class, $this->createRenderOnlyDecorator());

        $select = new Select('foo');
        $select->where(['id' => 1]);
        $this->platform->setSubject($select);

        $statement = new StatementContainer();
        $result    = $this->platform->prepareStatement($this->createAdapter(), $statement);

        static::assertSame($statement, $result);
        static::assertStringContainsString('SELECT', $statement->getSql());
    }

    #[Test]
    public function prepareStatementRendersTheSubject(): void
    {
        $select = new Select('foo');
        $select->where(['id' => 1]);
        $this->platform->setSubject($select);

        $statement = new StatementContainer();
        $result    = $this->platform->prepareStatement($this->createAdapter(), $statement);

        static::assertSame($statement, $result);
        static::assertStringContainsString('SELECT', $statement->getSql());
    }

    #[Test]
    public function prepareStatementThrowsWhenSubjectNotPreparable(): void
    {
        $subject = $this->createMock(SqlInterface::class);
        $this->platform->setSubject($subject);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::SUBJECT_NOT_IMPLEMENTING,
            PreparableSqlInterface::class,
            'prepareStatement()',
        ));
        $this->platform->prepareStatement($this->createAdapter(), new StatementContainer());
    }

    #[Test]
    public function setSubjectReturnsStatic(): void
    {
        $subject = $this->createMock(SqlInterface::class);
        $result  = $this->platform->setSubject($subject);

        static::assertSame($this->platform, $result);
    }

    #[Test]
    public function setTypeDecoratorThrowsWhenTheDecoratorDoesNotImplementTheRegisteredType(): void
    {
        $decorator = $this->createStub(PlatformDecoratorInterface::class);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::DECORATOR_WRONG_TYPE,
            $decorator::class,
            Select::class,
        ));
        $this->platform->setTypeDecorator(Select::class, $decorator);
    }

    protected function setUp(): void
    {
        $this->platform = new AbstractPlatform();
    }

    private function createAdapter(): AdapterInterface
    {
        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('quoteIdentifier')->willReturnCallback(static fn($v) => "\"{$v}\"");
        $mockPlatform->method('quoteIdentifierInFragment')->willReturnCallback(static fn($v) => "\"{$v}\"");
        $mockPlatform->method('getIdentifierSeparator')->willReturn('.');
        $mockPlatform->method('getSqlPlatformDecorator')->willReturn($this->platform);

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->method('formatParameterName')->willReturn('?');

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getPlatform')->willReturn($mockPlatform);
        $adapter->method('getDriver')->willReturn($mockDriver);

        return $adapter;
    }

    private function createPrepareOnlyDecorator(): PlatformDecoratorInterface
    {
        return new class implements PlatformDecoratorInterface, PreparableSqlInterface {
            public function prepareStatement(
                AdapterInterface $adapter,
                StatementContainerInterface $statementContainer,
            ): StatementContainerInterface {
                return $statementContainer;
            }

            public function setSubject(SqlInterface|PreparableSqlInterface|null $subject): static
            {
                return $this;
            }
        };
    }

    private function createRenderableDecorator(): PlatformDecoratorInterface
    {
        return new class extends Select implements PlatformDecoratorInterface {
            public SqlInterface|PreparableSqlInterface|null $boundSubject = null;

            public function getSqlString(?PlatformInterface $adapterPlatform = null): string
            {
                return 'rendered by the decorator';
            }

            public function setSubject(SqlInterface|PreparableSqlInterface|null $subject): static
            {
                $this->boundSubject = $subject;
                $this->subject      = $subject;

                return $this;
            }
        };
    }

    private function createRenderOnlyDecorator(): PlatformDecoratorInterface
    {
        return new class implements PlatformDecoratorInterface, SqlInterface {
            public function getSqlString(?PlatformInterface $adapterPlatform = null): string
            {
                return 'rendered by the decorator';
            }

            public function setSubject(SqlInterface|PreparableSqlInterface|null $subject): static
            {
                return $this;
            }
        };
    }
}
