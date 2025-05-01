<?php

namespace LaminasTest\Db\TableGateway;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Sql\Update;
use Laminas\Db\TableGateway\Exception\InvalidArgumentException;
use Laminas\Db\TableGateway\Feature;
use Laminas\Db\TableGateway\Feature\FeatureSet;
use Laminas\Db\TableGateway\TableGateway;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-type AliasedTable = array{alias: string|TableIdentifier}
 */
final class TableGatewayTest extends TestCase
{
    protected Adapter&MockObject $mockAdapter;

    #[Override]
    protected function setUp(): void
    {
        // mock the adapter, driver, and parts
        $mockResult    = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);
        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $mockDriver     = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        // setup mock adapter
        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver])
            ->getMock();
    }

    /**
     * Beside other tests checks for plain string table identifier
     */
    public function testConstructor(): void
    {
        // constructor with only required args
        $table = new TableGateway(
            'foo',
            $this->mockAdapter
        );

        self::assertEquals('foo', $table->getTable());
        self::assertSame($this->mockAdapter, $table->getAdapter());
        self::assertInstanceOf(FeatureSet::class, $table->getFeatureSet());
        self::assertInstanceOf(ResultSet::class, $table->getResultSetPrototype());
        self::assertInstanceOf(Sql::class, $table->getSql());

        // injecting all args
        $table          = new TableGateway(
            'foo',
            $this->mockAdapter,
            $featureSet = new Feature\FeatureSet(),
            $resultSet  = new ResultSet(),
            $sql        = new Sql($this->mockAdapter, 'foo')
        );

        self::assertEquals('foo', $table->getTable());
        self::assertSame($this->mockAdapter, $table->getAdapter());
        self::assertSame($featureSet, $table->getFeatureSet());
        self::assertSame($resultSet, $table->getResultSetPrototype());
        self::assertSame($sql, $table->getSql());

        // constructor expects exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name must be a string or an instance of Laminas\Db\Sql\TableIdentifier');
        /** @psalm-suppress NullArgument - Testing incorrect constructor */
        new TableGateway(
            null,
            $this->mockAdapter
        );
    }

    #[Group('6726')]
    #[Group('6740')]
    public function testTableAsString(): void
    {
        $ti = 'fooTable.barSchema';
        // constructor with only required args
        $table = new TableGateway(
            $ti,
            $this->mockAdapter
        );

        self::assertEquals($ti, $table->getTable());
    }

    #[Group('6726')]
    #[Group('6740')]
    public function testTableAsTableIdentifierObject(): void
    {
        $ti = new TableIdentifier('fooTable', 'barSchema');
        // constructor with only required args
        $table = new TableGateway(
            $ti,
            $this->mockAdapter
        );

        self::assertEquals($ti, $table->getTable());
    }

    #[Group('6726')]
    #[Group('6740')]
    public function testTableAsAliasedTableIdentifierObject(): void
    {
        // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        $aliasedTI = ['foo' => new TableIdentifier('fooTable', 'barSchema')];
        // constructor with only required args
        $table = new TableGateway(
            $aliasedTI,
            $this->mockAdapter
        );

        self::assertEquals($aliasedTI, $table->getTable());
        // phpcs:enable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
    }

    /**
     * @psalm-return array{
     *     'identifier-alias': list{array{U: TableIdentifier}, TableIdentifier},
     *     'simple-alias': list{array{U: string}, string}
     * }
     */
    public static function aliasedTables(): array
    {
        $identifier = new TableIdentifier('Users');
        return [
            'simple-alias'     => [['U' => 'Users'], 'Users'],
            'identifier-alias' => [['U' => $identifier], $identifier],
        ];
    }

    /**
     * @param AliasedTable           $tableValue
     */
    #[DataProvider('aliasedTables')]
    #[Group('7311')]
    public function testInsertShouldResetTableToUnaliasedTable(
        array $tableValue,
        string|TableIdentifier $expected
    ): void {
        $insert = new Insert();
        $insert->into($tableValue);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->getMock();
        $result->expects($this->once())
            ->method('getAffectedRows')
            ->willReturn(1);

        $statement = $this->getMockBuilder(StatementInterface::class)
            ->getMock();
        $statement->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $statementExpectation = function (Insert $insert) use ($expected, $statement): MockObject&StatementInterface {
            $state = $insert->getRawState();
            $this->assertIsArray($state);
            self::assertSame($expected, $state['table']);
            return $statement;
        };

        $sql = $this->getMockBuilder(Sql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->atLeastOnce())
            ->method('getTable')
            ->willReturn($tableValue);
        $sql->expects($this->once())
            ->method('insert')
            ->willReturn($insert);
        $sql->expects($this->once())
            ->method('prepareStatementForSqlObject')
            ->with($this->equalTo($insert))
            ->willReturnCallback($statementExpectation);

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql
        );

        $table->insert([
            'foo' => 'FOO',
        ]);

        $state = $insert->getRawState();
        $this->assertIsArray($state);
        $this->assertIsArray($state['table']);
        $this->assertEquals(
            $tableValue,
            $state['table']
        );
    }

    /**
     * @param AliasedTable           $tableValue
     */
    #[DataProvider('aliasedTables')]
    public function testUpdateShouldResetTableToUnaliasedTable(
        array $tableValue,
        string|TableIdentifier $expected
    ): void {
        $update = new Update();
        $update->table($tableValue);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->getMock();
        $result->expects($this->once())
            ->method('getAffectedRows')
            ->willReturn(1);

        $statement = $this->getMockBuilder(StatementInterface::class)
            ->getMock();
        $statement->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $statementExpectation = function (Update $update) use ($expected, $statement): MockObject&StatementInterface {
            $state = $update->getRawState();
            $this->assertIsArray($state);
            $this->assertSame($expected, $state['table']);
            return $statement;
        };

        $sql = $this->getMockBuilder(Sql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->atLeastOnce())
            ->method('getTable')
            ->willReturn($tableValue);
        $sql->expects($this->once())
            ->method('update')
            ->willReturn($update);
        $sql->expects($this->once())
            ->method('prepareStatementForSqlObject')
            ->with($this->equalTo($update))
            ->willReturnCallback($statementExpectation);

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql
        );

        $table->update([
            'foo' => 'FOO',
        ], [
            'bar' => 'BAR',
        ]);

        $state = $update->getRawState();
        $this->assertIsArray($state);
        $this->assertIsArray($state['table']);
        $this->assertEquals(
            $tableValue,
            $state['table']
        );
    }

    /**
     * @param AliasedTable           $tableValue
     */
    #[DataProvider('aliasedTables')]
    public function testDeleteShouldResetTableToUnaliasedTable(
        array $tableValue,
        string|TableIdentifier $expected
    ): void {
        $delete = new Delete();
        $delete->from($tableValue);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->getMock();
        $result->expects($this->once())
            ->method('getAffectedRows')
            ->willReturn(1);

        $statement = $this->getMockBuilder(StatementInterface::class)
            ->getMock();
        $statement->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $statementExpectation = function (Delete $delete) use ($expected, $statement): MockObject&StatementInterface {
            $state = $delete->getRawState();
            $this->assertIsArray($state);
            $this->assertSame($expected, $state['table']);
            return $statement;
        };

        $sql = $this->getMockBuilder(Sql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->atLeastOnce())
            ->method('getTable')
            ->willReturn($tableValue);
        $sql->expects($this->once())
            ->method('delete')
            ->willReturn($delete);
        $sql->expects($this->once())
            ->method('prepareStatementForSqlObject')
            ->with($this->equalTo($delete))
            ->willReturnCallback($statementExpectation);

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql
        );

        $table->delete([
            'foo' => 'FOO',
        ]);

        $state = $delete->getRawState();

        $this->assertIsArray($state);
        $this->assertIsArray($state['table']);
        $this->assertEquals(
            $tableValue,
            $state['table']
        );
    }
}
