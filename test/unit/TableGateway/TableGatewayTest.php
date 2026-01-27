<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\Exception\InvalidArgumentException;
use PhpDb\TableGateway\Feature;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TypeError;

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
        $mockPlatform = $this->getMockBuilder(PlatformInterface::class)->getMock();

        // setup mock adapter
        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver, $mockPlatform])
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

        // constructor expects exception - native type declaration throws TypeError for null table
        $this->expectException(TypeError::class);
        new TableGateway(
            /** @phpstan-ignore argument.type */
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

    public function testConstructorThrowsExceptionWhenSqlTableDoesNotMatch(): void
    {
        $sql = new Sql($this->mockAdapter, 'bar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The table inside the provided Sql object must match the table of this TableGateway'
        );

        new TableGateway('foo', $this->mockAdapter, null, null, $sql);
    }

    public function testConstructorWithSingleFeature(): void
    {
        $feature = new Feature\SequenceFeature('id', 'foo_seq');

        $table = new TableGateway('foo', $this->mockAdapter, $feature);

        $featureSet = $table->getFeatureSet();
        self::assertSame($feature, $featureSet->getFeatureByClassName(Feature\SequenceFeature::class));
    }

    public function testConstructorWithArrayOfFeatures(): void
    {
        $feature1 = new Feature\SequenceFeature('id', 'foo_seq');
        $feature2 = new Feature\GlobalAdapterFeature();

        // Set up global adapter for GlobalAdapterFeature
        Feature\GlobalAdapterFeature::setStaticAdapter($this->mockAdapter);

        $table = new TableGateway('foo', $this->mockAdapter, [$feature1, $feature2]);

        $featureSet = $table->getFeatureSet();
        self::assertInstanceOf(FeatureSet::class, $featureSet);
        self::assertSame($feature1, $featureSet->getFeatureByClassName(Feature\SequenceFeature::class));
        self::assertSame($feature2, $featureSet->getFeatureByClassName(Feature\GlobalAdapterFeature::class));

        // Clean up static adapter
        $reflection = new ReflectionProperty(Feature\GlobalAdapterFeature::class, 'staticAdapters');
        $reflection->setValue(null, []);
    }

    public function testConstructorWithFeatureSet(): void
    {
        $feature    = new Feature\SequenceFeature('id', 'foo_seq');
        $featureSet = new FeatureSet([$feature]);

        $table = new TableGateway('foo', $this->mockAdapter, $featureSet);

        self::assertSame($featureSet, $table->getFeatureSet());
    }

    public function testConstructorWithCustomResultSetPrototype(): void
    {
        $resultSet = new ResultSet();

        $table = new TableGateway('foo', $this->mockAdapter, null, $resultSet);

        self::assertSame($resultSet, $table->getResultSetPrototype());
    }
}
