<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use PhpDb\TableGateway\Feature\MasterSlaveFeature;
use PhpDb\TableGateway\Feature\PrimaryReplicaFeature;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[IgnoreDeprecations]
final class MasterSlaveFeatureTest extends TestCase
{
    protected MockObject&AdapterInterface $mockSlaveAdapter;
    protected MasterSlaveFeature $feature;

    #[Test]
    public function extendsPrimaryReplicaFeature(): void
    {
        static::assertInstanceOf(PrimaryReplicaFeature::class, $this->feature);
    }

    #[Test]
    public function getSlaveAdapterProxiesToGetReplicaAdapter(): void
    {
        static::assertSame($this->feature->getReplicaAdapter(), $this->feature->getSlaveAdapter());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getSlaveSqlProxiesToGetReplicaSql(): void
    {
        $slaveSql = new Sql($this->mockSlaveAdapter, 'foo');
        $feature  = new MasterSlaveFeature($this->mockSlaveAdapter, $slaveSql);

        static::assertSame($feature->getReplicaSql(), $feature->getSlaveSql());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->mockSlaveAdapter = $this->getMockBuilder(AdapterInterface::class)->onlyMethods([])->getMock();
        $this->feature          = new MasterSlaveFeature($this->mockSlaveAdapter);
    }
}
