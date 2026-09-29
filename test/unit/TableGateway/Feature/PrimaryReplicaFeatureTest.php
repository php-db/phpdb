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
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\PrimaryReplicaFeature;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PrimaryReplicaFeatureTest extends TestCase
{
    protected MockObject&AdapterInterface $mockPrimaryAdapter;
    protected MockObject&AdapterInterface $mockReplicaAdapter;
    protected MockObject&StatementInterface $mockStatement;
    protected PrimaryReplicaFeature $feature;

    /**
     * @throws Exception
     */
    #[Test]
    public function constructorWithReplicaSql(): void
    {
        $replicaSql = new Sql($this->mockReplicaAdapter, 'foo');
        $feature    = new PrimaryReplicaFeature($this->mockReplicaAdapter, $replicaSql);

        static::assertSame($replicaSql, $feature->getReplicaSql());
    }

    #[Test]
    public function getReplicaAdapter(): void
    {
        static::assertSame($this->mockReplicaAdapter, $this->feature->getReplicaAdapter());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function postInitialize(): void
    {
        $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockPrimaryAdapter, $this->feature])
            ->onlyMethods([])
            ->getMock();
        // postInitialize is run
        static::assertSame($this->mockReplicaAdapter, $this->feature->getReplicaSql()->getAdapter());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function postInitializeThrowsWhenTableGatewayHasNoSql(): void
    {
        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $feature = new PrimaryReplicaFeature($this->mockReplicaAdapter);
        $feature->setTableGateway($tableGateway);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_SQL_INSTANCE);

        $feature->postInitialize();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function postInitializeWithProvidedReplicaSql(): void
    {
        $replicaSql = new Sql($this->mockReplicaAdapter, 'foo');
        $feature    = new PrimaryReplicaFeature($this->mockReplicaAdapter, $replicaSql);

        $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockPrimaryAdapter, $feature])
            ->onlyMethods([])
            ->getMock();

        // The provided replicaSql should be used instead of creating a new one
        static::assertSame($replicaSql, $feature->getReplicaSql());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function postSelect(): void
    {
        $table = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockPrimaryAdapter, $this->feature])
            ->onlyMethods([])
            ->getMock();

        /** @var MockObject&StatementInterface $stmt */
        $stmt = $this->mockReplicaAdapter
            ->getDriver()
            ->createStatement();

        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(
                $this->getMockBuilder(ResultInterface::class)
                    ->onlyMethods([])
                    ->getMock(),
            );

        $primarySql = $table->getSql();
        $table->select('foo = bar');

        // test that the sql object is restored
        static::assertSame($primarySql, $table->getSql());
    }

    #[Test]
    public function postSelectThrowsWhenPostInitializeHasNotRun(): void
    {
        $feature = new PrimaryReplicaFeature($this->mockReplicaAdapter);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_PRIMARY_SQL);

        $feature->postSelect();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function preSelect(): void
    {
        $this->expectNotToPerformAssertions();

        $table = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['foo', $this->mockPrimaryAdapter, $this->feature])
            ->onlyMethods([])
            ->getMock();

        /** @var MockObject&StatementInterface $stmt */
        $stmt = $this->mockReplicaAdapter
            ->getDriver()
            ->createStatement();

        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockBuilder(ResultInterface::class)->onlyMethods([])->getMock());
        $table->select('foo = bar');
    }

    #[Override]
    protected function setUp(): void
    {
        $this->mockPrimaryAdapter = $this->getMockBuilder(AdapterInterface::class)->onlyMethods([])->getMock();
        $this->mockReplicaAdapter = $this->getMockBuilder(AdapterInterface::class)->onlyMethods([])->getMock();
        $this->mockStatement      = $this->getMockBuilder(StatementInterface::class)->onlyMethods([])->getMock();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->onlyMethods([])->getMock();
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturn(clone $this->mockStatement);
        $this->mockPrimaryAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $this->mockPrimaryAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->onlyMethods([])->getMock();
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturn(clone $this->mockStatement);
        $this->mockReplicaAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $this->mockReplicaAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $this->feature = new PrimaryReplicaFeature($this->mockReplicaAdapter);
    }
}
