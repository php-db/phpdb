<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\RowGateway\Exception\InvalidArgumentException;
use PhpDb\RowGateway\Exception\RuntimeException;
use PhpDb\RowGateway\RowGateway;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class RowGatewayTest extends TestCase
{
    /** @var Adapter&MockObject */
    protected Adapter|MockObject $mockAdapter;

    protected RowGateway $rowGateway;

    /** @var ResultInterface&MockObject */
    protected ResultInterface|MockObject $mockResult;

    #[Override]
    protected function setUp(): void
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->willReturn(1);
        $this->mockResult = $mockResult;

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);

        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs(
                [
                    $mockDriver,
                    $this->getMockBuilder(PlatformInterface::class)->getMock(),
                ]
            )->getMock();
    }

    public function testEmptyPrimaryKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a primary key column set.');
        $this->rowGateway = new RowGateway('', 'foo', $this->mockAdapter);
    }

    public function testConstructorWithStringPrimaryKey(): void
    {
        $rowGateway = new RowGateway('id', 'foo', $this->mockAdapter);

        $tableProp = new ReflectionProperty(RowGateway::class, 'table');
        $sqlProp   = new ReflectionProperty(RowGateway::class, 'sql');

        self::assertEquals('foo', $tableProp->getValue($rowGateway));
        self::assertInstanceOf(Sql::class, $sqlProp->getValue($rowGateway));
    }

    public function testConstructorWithArrayPrimaryKey(): void
    {
        $rowGateway = new RowGateway(['id', 'name'], 'foo', $this->mockAdapter);

        $tableProp = new ReflectionProperty(RowGateway::class, 'table');
        self::assertEquals('foo', $tableProp->getValue($rowGateway));

        $pkProp = new ReflectionProperty(RowGateway::class, 'primaryKeyColumn');
        self::assertEquals(['id', 'name'], $pkProp->getValue($rowGateway));
    }

    public function testConstructorWithNullPrimaryKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a primary key column set.');

        new RowGateway(null, 'foo', $this->mockAdapter);
    }

    public function testConstructorWithTableIdentifier(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'schema');
        $rowGateway      = new RowGateway('id', $tableIdentifier, $this->mockAdapter);

        $tableProp = new ReflectionProperty(RowGateway::class, 'table');
        self::assertSame($tableIdentifier, $tableProp->getValue($rowGateway));
    }

    public function testConstructorWithSqlObject(): void
    {
        $sql        = new Sql($this->mockAdapter, 'foo');
        $rowGateway = new RowGateway('id', 'foo', $sql);

        $sqlProp   = new ReflectionProperty(RowGateway::class, 'sql');
        $tableProp = new ReflectionProperty(RowGateway::class, 'table');

        self::assertSame($sql, $sqlProp->getValue($rowGateway));
        self::assertEquals('foo', $tableProp->getValue($rowGateway));
    }

    public function testConstructorThrowsExceptionWhenSqlTableDoesNotMatch(): void
    {
        $sql = new Sql($this->mockAdapter, 'bar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Sql object provided does not have a table that matches this row object');

        new RowGateway('id', 'foo', $sql);
    }

    public function testInitializeReturnsEarlyWhenAlreadyInitialized(): void
    {
        $rowGateway = new RowGateway('id', 'foo', $this->mockAdapter);

        $isInitializedProp = new ReflectionProperty(RowGateway::class, 'isInitialized');
        self::assertTrue($isInitializedProp->getValue($rowGateway));

        $rowGateway->initialize();

        self::assertTrue($isInitializedProp->getValue($rowGateway));
    }

    public function testInitializeThrowsWhenTableIsNull(): void
    {
        $rowGateway = new RowGateway('id', 'foo', $this->mockAdapter);

        $isInitializedProp = new ReflectionProperty(RowGateway::class, 'isInitialized');
        $isInitializedProp->setValue($rowGateway, false);

        $tableProp = new ReflectionProperty(RowGateway::class, 'table');
        $tableProp->setValue($rowGateway, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a valid table set.');

        $rowGateway->initialize();
    }

    public function testInitializeThrowsWhenSqlIsNull(): void
    {
        $rowGateway = new RowGateway('id', 'foo', $this->mockAdapter);

        $isInitializedProp = new ReflectionProperty(RowGateway::class, 'isInitialized');
        $isInitializedProp->setValue($rowGateway, false);

        $sqlProp = new ReflectionProperty(RowGateway::class, 'sql');
        $sqlProp->setValue($rowGateway, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a Sql object set.');

        $rowGateway->initialize();
    }
}
