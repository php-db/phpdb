<?php

namespace LaminasTest\Db\TableGateway\Feature;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Platform\Sql92;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\Feature\MasterSlaveFeature;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MasterSlaveFeatureTest extends TestCase
{
    /** @var AdapterInterface&MockObject */
    protected AdapterInterface|MockObject $mockMasterAdapter;

    /** @var AdapterInterface&MockObject */
    protected AdapterInterface|MockObject $mockSlaveAdapter;

    /** @var MasterSlaveFeature */
    protected MasterSlaveFeature $feature;

    /** @var TableGateway&MockObject */
    protected TableGateway&MockObject $table;

    protected function setUp(): void
    {
        $this->mockMasterAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockDriver    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $this->mockMasterAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $this->mockMasterAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $this->mockSlaveAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockDriver    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $this->mockSlaveAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $this->mockSlaveAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $this->feature = new MasterSlaveFeature($this->mockSlaveAdapter);
    }

    /**
     * @throws Exception
     */
    public function testPostInitialize()
    {
        $this->getMockForAbstractClass(
            TableGateway::class,
            ['foo', $this->mockMasterAdapter, $this->feature]
        );
        // postInitialize is run
        self::assertSame($this->mockSlaveAdapter, $this->feature->getSlaveSql()->getAdapter());
    }

    /**
     * @throws Exception
     */
    public function testPreSelect()
    {
        $table = $this->getMockForAbstractClass(
            TableGateway::class,
            ['foo', $this->mockMasterAdapter, $this->feature]
        );

        $this->mockSlaveAdapter->getDriver()->createStatement()
            ->expects($this->once())->method('execute')->willReturn($this->getMockBuilder(ResultSet::class)->getMock());
        $table->select('foo = bar');
    }

    /**
     * @throws Exception
     */
    public function testPostSelect()
    {
        $table = $this->getMockForAbstractClass(
            TableGateway::class,
            ['foo', $this->mockMasterAdapter, $this->feature]
        );
        $this->mockSlaveAdapter->getDriver()->createStatement()
            ->expects($this->once())->method('execute')->willReturn($this->getMockBuilder(ResultSet::class)->getMock());

        $masterSql = $table->getSql();
        $table->select('foo = bar');

        // test that the sql object is restored
        self::assertSame($masterSql, $table->getSql());
    }
}
