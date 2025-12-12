<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\CoversMethod;
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

    // @codingStandardsIgnoreStart
    public function test__construct(): void
    {
        // @codingStandardsIgnoreEnd
        $sql = new Sql($this->mockAdapter);

        self::assertFalse($sql->hasTable());

        $sql->setTable('foo');
        self::assertSame('foo', $sql->getTable());

        $this->expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $sql->setTable(null);
    }

    public function testSelect(): void
    {
        $select = $this->sql->select();
        self::assertInstanceOf(Select::class, $select);
        $table = $select->getRawState('table');
        self::assertInstanceOf(TableIdentifier::class, $table);
        self::assertEquals('foo', $table->getTable());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.'
        );
        $this->sql->select('bar');
    }

    public function testInsert(): void
    {
        $insert = $this->sql->insert();
        self::assertInstanceOf(Insert::class, $insert);
        $table = $insert->getRawState('table');
        self::assertInstanceOf(TableIdentifier::class, $table);
        self::assertEquals('foo', $table->getTable());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.'
        );
        $this->sql->insert('bar');
    }

    public function testUpdate(): void
    {
        $update = $this->sql->update();
        self::assertInstanceOf(Update::class, $update);
        $table = $update->getRawState('table');
        self::assertInstanceOf(TableIdentifier::class, $table);
        self::assertEquals('foo', $table->getTable());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.'
        );
        $this->sql->update('bar');
    }

    public function testDelete(): void
    {
        $delete = $this->sql->delete();

        self::assertInstanceOf(Delete::class, $delete);
        $table = $delete->getRawState('table');
        self::assertInstanceOf(TableIdentifier::class, $table);
        self::assertEquals('foo', $table->getTable());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'This Sql object is intended to work with only the table "foo" provided at construction time.'
        );
        $this->sql->delete('bar');
    }

    public function testPrepareStatementForSqlObject(): void
    {
        $insert = $this->sql->insert()->columns(['foo'])->values(['foo' => 'bar']);
        $stmt   = $this->sql->prepareStatementForSqlObject($insert);
        self::assertInstanceOf(StatementInterface::class, $stmt);
    }

    public function testBuildSqlString(): void
    {
        $select    = $this->sql->select()->where(['bar' => 'baz']);
        $sqlString = $this->sql->buildSqlString($select);
        self::assertEquals('SELECT "foo".* FROM "foo" WHERE "bar" = \'baz\'', $sqlString);
    }
}
