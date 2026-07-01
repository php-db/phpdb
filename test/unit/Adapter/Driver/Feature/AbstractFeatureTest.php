<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Feature;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Feature\AbstractFeature;
use PhpDb\Adapter\Driver\Feature\DriverFeatureInterface;
use PhpDbTest\Adapter\Driver\Feature\TestAsset\TestDriverFeature;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(AbstractFeature::class, 'setDriver')]
final class AbstractFeatureTest extends TestCase
{
    public function testSetDriverStoresDriverAndReturnsInstance(): void
    {
        $feature = new TestDriverFeature();
        $driver  = $this->createMock(DriverInterface::class);

        $result = $feature->setDriver($driver);

        self::assertInstanceOf(DriverFeatureInterface::class, $result);
        self::assertSame($feature, $result);
    }
}
