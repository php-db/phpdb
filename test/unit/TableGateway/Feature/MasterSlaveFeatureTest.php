<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Sql;
use PhpDb\TableGateway\Feature\MasterSlaveFeature;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MasterSlaveFeatureTest extends TestCase
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
        $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockMasterAdapter, $this->feature])
            ->onlyMethods([])
            ->getMock();
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

        /** @var MockObject&StatementInterface $stmt */
        $stmt = $this
            ->mockSlaveAdapter
            ->getDriver()
            ->createStatement();

        $stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockBuilder(ResultInterface::class)->onlyMethods([])->getMock());
        $table->select('foo = bar');
    }

    /**
     * @throws Exception
     */
    public function testPostSelect(): void
    {
        $table = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockMasterAdapter, $this->feature])
            ->onlyMethods([])
            ->getMock();

        /** @var MockObject&StatementInterface $stmt */
        $stmt = $this
            ->mockSlaveAdapter
            ->getDriver()
            ->createStatement();

        $stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn(
                $this->getMockBuilder(ResultInterface::class)
                    ->onlyMethods([])
                    ->getMock()
            );

        $masterSql = $table->getSql();
        $table->select('foo = bar');

        // test that the sql object is restored
        self::assertSame($masterSql, $table->getSql());
    }

    public function testGetSlaveAdapter(): void
    {
        self::assertSame($this->mockSlaveAdapter, $this->feature->getSlaveAdapter());
    }

    /**
     * @throws Exception
     */
    public function testConstructorWithSlaveSql(): void
    {
        $slaveSql = new Sql($this->mockSlaveAdapter, 'foo');
        $feature  = new MasterSlaveFeature($this->mockSlaveAdapter, $slaveSql);

        self::assertSame($slaveSql, $feature->getSlaveSql());
    }

    /**
     * @throws Exception
     */
    public function testPostInitializeWithProvidedSlaveSql(): void
    {
        $slaveSql = new Sql($this->mockSlaveAdapter, 'foo');
        $feature  = new MasterSlaveFeature($this->mockSlaveAdapter, $slaveSql);

        $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockMasterAdapter, $feature])
            ->onlyMethods([])
            ->getMock();

        // The provided slaveSql should be used instead of creating a new one
        self::assertSame($slaveSql, $feature->getSlaveSql());
    }
}
