<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversMethod(Sql::class, '__construct')]
#[CoversMethod(Sql::class, 'getAdapter')]
#[CoversMethod(Sql::class, 'hasTable')]
#[CoversMethod(Sql::class, 'setTable')]
#[CoversMethod(Sql::class, 'getTable')]
#[CoversMethod(Sql::class, 'getSqlPlatform')]
#[CoversMethod(Sql::class, 'select')]
#[CoversMethod(Sql::class, 'insert')]
#[CoversMethod(Sql::class, 'update')]
#[CoversMethod(Sql::class, 'delete')]
#[CoversMethod(Sql::class, 'prepareStatementForSqlObject')]
#[CoversMethod(Sql::class, 'buildSqlString')]
final class SqlTest extends TestCase
{
    protected MockObject&Adapter $mockAdapter;

    /**
     * Sql object
     */
    protected Sql $sql;

    /** @return array<string, array{'delete'|'insert'|'update'}> */
    public static function dataChangingStatementProvider(): array
    {
        return [
            'delete' => ['delete'],
            'insert' => ['insert'],
            'update' => ['update'],
        ];
    }

    // @codingStandardsIgnoreStart
    #[Test]
    public function _construct(): void
    {
        // @codingStandardsIgnoreEnd
        $sql = new Sql($this->mockAdapter);

        static::assertFalse($sql->hasTable());

        $sql->setTable('foo');
        static::assertSame('foo', $sql->getTable());

        self::expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $sql->setTable(null);
    }

    #[Test]
    public function buildSqlString(): void
    {
        $select    = $this->sql->select()->where(['bar' => 'baz']);
        $sqlString = $this->sql->buildSqlString($select);
        static::assertSame('SELECT "foo".* FROM "foo" WHERE "bar" = \'baz\'', $sqlString);
    }

    #[Test]
    public function buildSqlStringThrowsWhenPlatformNotSqlInterface(): void
    {
        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $platform  = $this->createMock(PlatformInterface::class);
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);

        $adapter = $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([
                $this->createMock(DriverInterface::class),
                $platform,
            ])
            ->getMock();
        $adapter->method('getPlatform')->willReturn($platform);

        $sql = new Sql($adapter);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::SUBJECT_NOT_SQL_INTERFACE);
        $sql->buildSqlString($this->sql->select());
    }

    /**
     * @param 'delete'|'insert'|'update' $method
     */
    #[Test]
    #[DataProvider('dataChangingStatementProvider')]
    public function dataChangingStatementKeepsAliasedTable(string $method): void
    {
        $sql = new Sql($this->mockAdapter, ['f' => 'foo']);

        static::assertSame(['f' => 'foo'], $sql->{$method}()->getRawState('table'));
    }

    #[Test]
    public function delete(): void
    {
        $delete = $this->sql->delete();

        static::assertInstanceOf(Delete::class, $delete);
        static::assertSame('foo', $delete->getRawState('table'));

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->delete('bar');
    }

    #[Test]
    public function deleteThrowsWhenTableConflicts(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->delete(new TableIdentifier('bar'));
    }

    #[Test]
    public function getSqlPlatformReturnsPlatformDecorator(): void
    {
        static::assertInstanceOf(PlatformDecoratorInterface::class, $this->sql->getSqlPlatform());
    }

    #[Test]
    public function insert(): void
    {
        $insert = $this->sql->insert();
        static::assertInstanceOf(Insert::class, $insert);
        static::assertSame('foo', $insert->getRawState('table'));

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->insert('bar');
    }

    #[Test]
    public function insertThrowsWhenTableConflicts(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->insert(new TableIdentifier('bar'));
    }

    #[Test]
    public function prepareStatementForSqlObject(): void
    {
        $insert = $this->sql->insert()->columns(['foo'])->values(['foo' => 'bar']);
        $stmt   = $this->sql->prepareStatementForSqlObject($insert);
        static::assertInstanceOf(StatementInterface::class, $stmt);
    }

    #[Test]
    public function prepareStatementThrowsWhenPlatformNotPreparable(): void
    {
        $decorator = $this->createMock(PlatformDecoratorInterface::class);
        $platform  = $this->createMock(PlatformInterface::class);
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);

        $adapter = $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([
                $this->createMock(DriverInterface::class),
                $platform,
            ])
            ->getMock();
        $adapter->method('getPlatform')->willReturn($platform);

        $sql = new Sql($adapter);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::SUBJECT_NOT_PREPARABLE_SQL_INTERFACE);
        $sql->prepareStatementForSqlObject($this->sql->select());
    }

    #[Test]
    public function select(): void
    {
        $select = $this->sql->select();
        static::assertInstanceOf(Select::class, $select);
        static::assertSame('foo', $select->getRawState('table'));

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->select('bar');
    }

    #[Test]
    public function selectThrowsWhenTableConflicts(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->select(new TableIdentifier('bar'));
    }

    #[Test]
    public function update(): void
    {
        $update = $this->sql->update();
        static::assertInstanceOf(Update::class, $update);
        static::assertSame('foo', $update->getRawState('table'));

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->update('bar');
    }

    #[Test]
    public function updateThrowsWhenTableConflicts(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.',
        );
        $this->sql->update(new TableIdentifier('bar'));
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        // mock the adapter, driver, and parts
        $mockResult = $this->createMock(ResultInterface::class);

        $mockStatement = $this->createMock(StatementInterface::class);
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);

        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->onlyMethods([])->getMock();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->onlyMethods([])->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');

        // setup mock adapter
        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $mockDriver,
                new TestAsset\TrustingSql92Platform(),
                new TestAsset\TemporaryResultSet(),
            ])
            ->getMock();

        $this->sql = new Sql($this->mockAdapter, 'foo');
    }
}
