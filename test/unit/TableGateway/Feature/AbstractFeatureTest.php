<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\AbstractFeature;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AbstractFeatureTest extends TestCase
{
    private AbstractFeature&MockObject $feature;

    #[Test]
    public function getMagicMethodSpecificationsReturnsEmptyArray(): void
    {
        $result = $this->feature->getMagicMethodSpecifications();

        static::assertEmpty($result);
    }

    #[Test]
    public function getNameReturnsClassName(): void
    {
        $name = $this->feature->getName();

        static::assertNotEmpty($name);
    }

    #[Test]
    public function initializeDoesNothing(): void
    {
        // initialize() is a no-op, just verify it doesn't throw
        $this->feature->initialize();

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        static::assertTrue(true);
    }

    #[Test]
    public function setTableGateway(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feature->setTableGateway($tableGateway);

        $reflection = new ReflectionProperty(AbstractFeature::class, 'tableGateway');
        $value      = $reflection->getValue($this->feature);

        static::assertSame($tableGateway, $value);
    }

    protected function setUp(): void
    {
        $this->feature = $this->getMockBuilder(AbstractFeature::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }
}
