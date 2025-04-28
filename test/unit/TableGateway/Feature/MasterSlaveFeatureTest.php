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
    protected MockObject&AdapterInterface $mockMasterAdapter;
    protected MockObject&AdapterInterface $mockSlaveAdapter;
    protected MockObject&StatementInterface $mockStatement;
    protected MasterSlaveFeature $feature;
    protected TableGateway&MockObject $table;

    #[Override]
    protected function setUp(): void
    {
        $this->mockMasterAdapter = $this->getMockBuilder(AdapterInterface::class)->onlyMethods([])->getMock();
        $this->mockSlaveAdapter  = $this->getMockBuilder(AdapterInterface::class)->onlyMethods([])->getMock();
        $this->mockStatement     = $this->getMockBuilder(StatementInterface::class)->onlyMethods([])->getMock();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->onlyMethods([])->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn(clone $this->mockStatement);
        $this->mockMasterAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $this->mockMasterAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->onlyMethods([])->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn(clone $this->mockStatement);
        $this->mockSlaveAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $this->mockSlaveAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $this->feature = new MasterSlaveFeature($this->mockSlaveAdapter);
    }

    /**
     * @throws Exception
     */
    public function testPostInitialize(): void
    {
        $this->getMockBuilder(TableGateway::class)->setConstructorArgs(['foo', $this->mockMasterAdapter, $this->feature])->onlyMethods([])->getMock();
        // postInitialize is run
        self::assertSame($this->mockSlaveAdapter, $this->feature->getSlaveSql()->getAdapter());
    }

    /**
     * @throws Exception
     */
    public function testPreSelect(): void
    {
        $this->expectNotToPerformAssertions();

        $table = $this
            ->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockMasterAdapter, $this->feature])
            ->onlyMethods([])->getMock();

        $stmt = $this
            ->mockSlaveAdapter
            ->getDriver()
            ->createStatement();

        /** @var MockObject&StatementInterface $stmt */
        $stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockBuilder(ResultSet::class)->onlyMethods([])->getMock());
        $table->select('foo = bar');
    }

    /**
     * @throws Exception
     */
    public function testPostSelect(): void
    {
        $table = $this->getMockBuilder(TableGateway::class)->setConstructorArgs(['foo', $this->mockMasterAdapter, $this->feature])->onlyMethods([])->getMock();
        $stmt  = $this
            ->mockSlaveAdapter
            ->getDriver()
            ->createStatement();

        /** @var MockObject&StatementInterface $stmt */
        $stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockBuilder(ResultSet::class)->onlyMethods([])->getMock());

        $masterSql = $table->getSql();
        $table->select('foo = bar');

        // test that the sql object is restored
        self::assertSame($masterSql, $table->getSql());
    }
}
