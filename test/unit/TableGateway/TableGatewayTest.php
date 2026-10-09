<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
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
use PhpDbTest\TestAsset\TemporaryResultSet;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

    /** @return array<string, array{'delete'|'insert'|'update', list<array<string, int|string>>, string}> */
    public static function dataChangingCallOnAliasedTable(): array
    {
        return [
            'delete' => ['delete', [['id' => 5]], 'DELETE FROM "users" WHERE "id" = ?'],
            'insert' => ['insert', [['id' => 5]], 'INSERT INTO "users" ("id") VALUES (?)'],
            'update' => ['update', [['name' => 'x'], ['id' => 5]], 'UPDATE "users" SET "name" = ? WHERE "id" = ?'],
        ];
    }

    /**
     * Beside other tests checks for plain string table identifier
     */
    #[Test]
    public function constructor(): void
    {
        // constructor with only required args
        $table = new TableGateway(
            'foo',
            $this->mockAdapter,
        );

        static::assertSame('foo', $table->getTable());
        static::assertSame($this->mockAdapter, $table->getAdapter());
        static::assertInstanceOf(FeatureSet::class, $table->getFeatureSet());
        static::assertInstanceOf(ResultSet::class, $table->getResultSetPrototype());
        static::assertInstanceOf(Sql::class, $table->getSql());

        // injecting all args
        $table = new TableGateway(
            'foo',
            $this->mockAdapter,
            $featureSet = new Feature\FeatureSet(),
            $resultSet = new ResultSet(),
            $sql = new Sql($this->mockAdapter, 'foo'),
        );

        static::assertSame('foo', $table->getTable());
        static::assertSame($this->mockAdapter, $table->getAdapter());
        static::assertSame($featureSet, $table->getFeatureSet());
        static::assertSame($resultSet, $table->getResultSetPrototype());
        static::assertSame($sql, $table->getSql());

        // constructor expects exception - native type declaration throws TypeError for null table
        self::expectException(TypeError::class);
        /** @psalm-suppress NullArgument - Testing incorrect constructor */
        new TableGateway(
            null,
            $this->mockAdapter,
        );
    }

    #[Test]
    public function constructorThrowsExceptionWhenSqlTableDoesNotMatch(): void
    {
        $sql = new Sql($this->mockAdapter, 'bar');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'The table inside the provided Sql object must match the table of this TableGateway',
        );

        new TableGateway('foo', $this->mockAdapter, null, null, $sql);
    }

    #[Test]
    public function constructorWithArrayOfFeatures(): void
    {
        $feature1 = new Feature\SequenceFeature('id', 'foo_seq');
        $feature2 = new Feature\GlobalAdapterFeature();

        // Set up global adapter for GlobalAdapterFeature
        Feature\GlobalAdapterFeature::setStaticAdapter($this->mockAdapter);

        $table = new TableGateway('foo', $this->mockAdapter, [$feature1, $feature2]);

        $featureSet = $table->getFeatureSet();
        static::assertInstanceOf(FeatureSet::class, $featureSet);
        static::assertSame($feature1, $featureSet->getFeatureByClassName(Feature\SequenceFeature::class));
        static::assertSame($feature2, $featureSet->getFeatureByClassName(Feature\GlobalAdapterFeature::class));

        // Clean up static adapter
        $reflection = new ReflectionProperty(Feature\GlobalAdapterFeature::class, 'staticAdapters');
        $reflection->setValue(null, []);
    }

    #[Test]
    public function constructorWithCustomResultSetPrototype(): void
    {
        $resultSet = new ResultSet();

        $table = new TableGateway('foo', $this->mockAdapter, null, $resultSet);

        static::assertSame($resultSet, $table->getResultSetPrototype());
    }

    #[Test]
    public function constructorWithFeatureSet(): void
    {
        $feature    = new Feature\SequenceFeature('id', 'foo_seq');
        $featureSet = new FeatureSet([$feature]);

        $table = new TableGateway('foo', $this->mockAdapter, $featureSet);

        static::assertSame($featureSet, $table->getFeatureSet());
    }

    #[Test]
    public function constructorWithSingleFeature(): void
    {
        $feature = new Feature\SequenceFeature('id', 'foo_seq');

        $table = new TableGateway('foo', $this->mockAdapter, $feature);

        $featureSet = $table->getFeatureSet();
        static::assertInstanceOf(FeatureSet::class, $featureSet);
        static::assertSame($feature, $featureSet->getFeatureByClassName(Feature\SequenceFeature::class));
    }

    /**
     * @param 'delete'|'insert'|'update' $method
     * @param list<array<string, int|string>> $arguments
     */
    #[Test]
    #[DataProvider('dataChangingCallOnAliasedTable')]
    public function dataChangingCallOnAliasedTableRendersTheBareTable(
        string $method,
        array $arguments,
        string $expectedSql,
    ): void {
        $executed = [];
        $result   = $this->createStub(ResultInterface::class);
        $result->method('getAffectedRows')->willReturn(1);

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('formatParameterName')->willReturn('?');
        $driver->method('createStatement')
            ->willReturnCallback(
                function () use (&$executed, $result): StatementInterface {
                    $sql       = '';
                    $statement = $this->createStub(StatementInterface::class);
                    $container = new ParameterContainer();
                    $statement->method('getParameterContainer')->willReturn($container);
                    $statement->method('setSql')
                        ->willReturnCallback(
                            static function (string $value) use (&$sql, $statement): StatementInterface {
                                $sql = $value;
                                return $statement;
                            },
                        );
                    $statement->method('execute')
                        ->willReturnCallback(
                            static function () use (&$executed, &$sql, $result): ResultInterface {
                                $executed[] = $sql;
                                return $result;
                            },
                        );

                    return $statement;
                },
            );

        $adapter = new Adapter($driver, new TrustingSql92Platform(), new TemporaryResultSet());
        $table   = new TableGateway(['u' => 'users'], $adapter);

        $table->{$method}(...$arguments);

        static::assertSame([$expectedSql], $executed);
    }

    /**
     * @param AliasedTable           $tableValue
     */
    #[Test]
    #[DataProvider('aliasedTables')]
    public function deleteShouldResetTableToUnaliasedTable(
        array $tableValue,
        string|TableIdentifier $expected,
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
            ->with(static::equalTo($delete))
            ->willReturnCallback($statementExpectation);

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql,
        );

        $table->delete([
            'foo' => 'FOO',
        ]);

        $state = $delete->getRawState();

        static::assertIsArray($state);
        static::assertIsArray($state['table']);
        static::assertEquals(
            $tableValue,
            $state['table'],
        );
    }

    /**
     * @param AliasedTable           $tableValue
     */
    #[Test]
    #[DataProvider('aliasedTables')]
    #[Group('7311')]
    public function insertShouldResetTableToUnaliasedTable(
        array $tableValue,
        string|TableIdentifier $expected,
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
            ->with(static::equalTo($insert))
            ->willReturnCallback($statementExpectation);

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql,
        );

        $table->insert([
            'foo' => 'FOO',
        ]);

        $state = $insert->getRawState();
        static::assertIsArray($state);
        static::assertIsArray($state['table']);
        static::assertEquals(
            $tableValue,
            $state['table'],
        );
    }

    #[Test]
    #[Group('6726')]
    #[Group('6740')]
    public function tableAsAliasedTableIdentifierObject(): void
    {
        // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        $aliasedTI = ['foo' => new TableIdentifier('fooTable', 'barSchema')];
        // constructor with only required args
        $table = new TableGateway(
            $aliasedTI,
            $this->mockAdapter,
        );

        static::assertEquals($aliasedTI, $table->getTable());

        // phpcs:enable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
    }

    #[Test]
    #[Group('6726')]
    #[Group('6740')]
    public function tableAsString(): void
    {
        $ti = 'fooTable.barSchema';
        // constructor with only required args
        $table = new TableGateway(
            $ti,
            $this->mockAdapter,
        );

        static::assertEquals($ti, $table->getTable());
    }

    #[Test]
    #[Group('6726')]
    #[Group('6740')]
    public function tableAsTableIdentifierObject(): void
    {
        $ti = new TableIdentifier('fooTable', 'barSchema');
        // constructor with only required args
        $table = new TableGateway(
            $ti,
            $this->mockAdapter,
        );

        static::assertEquals($ti, $table->getTable());
    }

    /**
     * @param AliasedTable           $tableValue
     */
    #[Test]
    #[DataProvider('aliasedTables')]
    public function updateShouldResetTableToUnaliasedTable(
        array $tableValue,
        string|TableIdentifier $expected,
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
            ->with(static::equalTo($update))
            ->willReturnCallback($statementExpectation);

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql,
        );

        $table->update([
            'foo' => 'FOO',
        ], [
            'bar' => 'BAR',
        ]);

        $state = $update->getRawState();
        static::assertIsArray($state);
        static::assertIsArray($state['table']);
        static::assertEquals(
            $tableValue,
            $state['table'],
        );
    }

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
}
