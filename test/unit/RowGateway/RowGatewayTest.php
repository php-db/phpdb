<?php

namespace LaminasTest\Db\RowGateway;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\RowGateway\Exception\RuntimeException;
use PhpDb\RowGateway\RowGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowGatewayTest extends TestCase
{
    /** @var Adapter|MockObject */
    protected Adapter|MockObject $mockAdapter;

    protected RowGateway $rowGateway;

    /** @var MockObject|ResultInterface */
    protected ResultInterface|MockObject $mockResult;
    #[\Override]
    protected function setUp(): void
    {
        // mock the adapter, driver, and parts
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->willReturn(1);
        $this->mockResult = $mockResult;

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);

        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        // setup mock adapter
        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver])
            ->getMock();
    }

    public function testEmptyPrimaryKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a primary key column set.');
        $this->rowGateway = new RowGateway('', 'foo', $this->mockAdapter);
    }
}
