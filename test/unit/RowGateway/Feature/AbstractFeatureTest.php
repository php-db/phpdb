<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Exception\RuntimeException;
use PhpDb\RowGateway\Feature\AbstractFeature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AbstractFeatureTest extends TestCase
{
    private AbstractFeature&MockObject $feature;

    protected function setUp(): void
    {
        $this->feature = $this->getMockBuilder(AbstractFeature::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testGetNameReturnsClassName(): void
    {
        $name = $this->feature->getName();

        // The mock class name will contain the class name
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertIsString($name);
        self::assertNotEmpty($name);
    }

    public function testSetRowGateway(): void
    {
        /** @var AbstractRowGateway&MockObject $rowGateway */
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feature->setRowGateway($rowGateway);

        // Use reflection to verify the rowGateway was set
        $reflection = new ReflectionProperty(AbstractFeature::class, 'rowGateway');
        $value      = $reflection->getValue($this->feature);

        self::assertSame($rowGateway, $value);
    }

    public function testInitializeThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This method is not intended to be called on this object.');

        $this->feature->initialize();
    }

    public function testGetMagicMethodSpecificationsReturnsEmptyArray(): void
    {
        $result = $this->feature->getMagicMethodSpecifications();

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
