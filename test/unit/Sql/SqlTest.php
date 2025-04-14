<?php

namespace LaminasTest\Db\Sql;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use LaminasTest\Db\TestAsset;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversMethod(Sql::class, '__construct')]
#[CoversMethod(Sql::class, 'select')]
#[CoversMethod(Sql::class, 'insert')]
#[CoversMethod(Sql::class, 'update')]
#[CoversMethod(Sql::class, 'delete')]
#[CoversMethod(Sql::class, 'prepareStatementForSqlObject')]
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
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult::class);

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
        /** @psalm-suppress NullArgument - ensure an exception is thrown */
        $sql->setTable(null);
    }

    public function testSelect(): void
    {
        $select = $this->sql->select();
        self::assertInstanceOf(Select::class, $select);
        self::assertSame('foo', $select->getRawState('table'));

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
        self::assertSame('foo', $insert->getRawState('table'));

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
        self::assertSame('foo', $update->getRawState('table'));

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
        self::assertSame('foo', $delete->getRawState('table'));

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

    /**
     * @throws Exception
     */
    #[Group('6890')]
    public function testForDifferentAdapters(): void
    {
        $adapterSql92     = $this->getAdapterForPlatform('sql92');
        $adapterMySql     = $this->getAdapterForPlatform('MySql');
        $adapterOracle    = $this->getAdapterForPlatform('Oracle');
        $adapterSqlServer = $this->getAdapterForPlatform('SqlServer');

        $select = $this->sql->select()->offset(10);

        // Default
        self::assertEquals(
            'SELECT "foo".* FROM "foo" OFFSET \'10\'',
            $this->sql->buildSqlString($select)
        );

        $stmt = $this
            ->mockAdapter
            ->getDriver()
            ->createStatement();

        /** @var MockObject&StatementInterface $stmt */
        $stmt->expects($this->any())->method('setSql')
                ->with($this->equalTo('SELECT "foo".* FROM "foo" OFFSET ?'));
        $this->sql->prepareStatementForSqlObject($select);

        // Sql92
        self::assertEquals(
            'SELECT "foo".* FROM "foo" OFFSET \'10\'',
            $this->sql->buildSqlString($select, $adapterSql92)
        );
        $stmt = $adapterSql92
            ->getDriver()
            ->createStatement();

        /** @var MockObject&StatementInterface $stmt */
        $stmt->expects($this->any())->method('setSql')
                ->with($this->equalTo('SELECT "foo".* FROM "foo" OFFSET ?'));
        $this->sql->prepareStatementForSqlObject($select, null, $adapterSql92);

        // MySql
        self::assertEquals(
            'SELECT `foo`.* FROM `foo` LIMIT 18446744073709551615 OFFSET 10',
            $this->sql->buildSqlString($select, $adapterMySql)
        );
        $stmt = $adapterMySql
            ->getDriver()
            ->createStatement();

        /** @var MockObject&StatementInterface $stmt */
        $stmt->expects($this->any())->method('setSql')
                ->with($this->equalTo('SELECT `foo`.* FROM `foo` LIMIT 18446744073709551615 OFFSET ?'));
        $this->sql->prepareStatementForSqlObject($select, null, $adapterMySql);

        // Oracle
        self::assertEquals(
            'SELECT * FROM (SELECT b.*, rownum b_rownum FROM ( SELECT "foo".* FROM "foo" ) b ) WHERE b_rownum > (10)',
            $this->sql->buildSqlString($select, $adapterOracle)
        );

        $stmt = $adapterOracle
            ->getDriver()
            ->createStatement();

        // @codingStandardsIgnoreStart
        /** @var MockObject&StatementInterface $stmt */
        $stmt->expects($this->any())->method('setSql')
                ->with($this->equalTo('SELECT * FROM (SELECT b.*, rownum b_rownum FROM ( SELECT "foo".* FROM "foo" ) b ) WHERE b_rownum > (:offset)'));
        // @codingStandardsIgnoreEnd
        $this->sql->prepareStatementForSqlObject($select, null, $adapterOracle);

        // SqlServer
        self::assertStringContainsString(
            'WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN 10+1 AND 0+10',
            $this->sql->buildSqlString($select, $adapterSqlServer)
        );

        $stmt = $adapterSqlServer
            ->getDriver()
            ->createStatement();

        /** @var MockObject&StatementInterface $stmt */
        $stmt->expects($this->any())->method('setSql')
                ->with($this->stringContains(
                    'WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN ?+1 AND ?+?'
                ));
        $this->sql->prepareStatementForSqlObject($select, null, $adapterSqlServer);
    }

    /**
     * Data provider
     *
     * @throws Exception
     */
    protected function getAdapterForPlatform(string $platform): Adapter
    {
        $platform = match ($platform) {
            'sql92'     => new TestAsset\TrustingSql92Platform(),
            'MySql'     => new TestAsset\TrustingMysqlPlatform(),
            'Oracle'    => new TestAsset\TrustingOraclePlatform(),
            'SqlServer' => new TestAsset\TrustingSqlServerPlatform(),
            default     => null,
        };

        $mockResult = $this->createMock(ResultInterface::class);

        $mockStatement = $this->createMock(StatementInterface::class);
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult::class);

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->onlyMethods([])->getMock();
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);

        return new Adapter($mockDriver, $platform);
    }
}
