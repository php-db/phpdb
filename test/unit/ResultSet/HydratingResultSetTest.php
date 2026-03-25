<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use Exception;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\ClassMethodsHydrator;
use Override;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\HydratingResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversMethod(HydratingResultSet::class, 'setObjectPrototype')]
#[CoversMethod(HydratingResultSet::class, 'getObjectPrototype')]
#[CoversMethod(HydratingResultSet::class, 'setHydrator')]
#[CoversMethod(HydratingResultSet::class, 'getHydrator')]
#[CoversMethod(HydratingResultSet::class, 'current')]
#[CoversMethod(HydratingResultSet::class, 'toArray')]
#[CoversMethod(HydratingResultSet::class, '__construct')]
#[CoversMethod(HydratingResultSet::class, 'setRowPrototype')]
#[CoversMethod(HydratingResultSet::class, 'getRowPrototype')]
#[Group('unit')]
final class HydratingResultSetTest extends TestCase
{
    private string $arraySerializableHydratorClass;

    private string $classMethodsHydratorClass;

    #[Override]
    protected function setUp(): void
    {
        $this->arraySerializableHydratorClass = ArraySerializableHydrator::class;
        $this->classMethodsHydratorClass      = ClassMethodsHydrator::class;
    }

    public function testSetObjectPrototype(): void
    {
        $prototype1            = new stdClass();
        $prototype1->property1 = 'value1';
        $prototype2            = new stdClass();
        $prototype2->property2 = 'value2';
        $hydratingRs           = new HydratingResultSet();

        // First mutation
        $result = $hydratingRs->setObjectPrototype($prototype1);

        // Verify fluent interface
        self::assertSame($hydratingRs, $result);

        // Verify the first mutation occurred
        self::assertSame($prototype1, $hydratingRs->getObjectPrototype());

        // Second mutation to verify mutability
        $hydratingRs->setObjectPrototype($prototype2);

        // Verify the instance was actually mutated
        self::assertSame($prototype2, $hydratingRs->getObjectPrototype());
        self::assertNotSame($prototype1, $hydratingRs->getObjectPrototype());
    }

    public function testGetObjectPrototype(): void
    {
        $hydratingRs = new HydratingResultSet();
        // Verify getObjectPrototype() returns default ArrayObject prototype
        self::assertInstanceOf('ArrayObject', $hydratingRs->getObjectPrototype());
    }

    public function testSetHydrator(): void
    {
        $hydratingRs    = new HydratingResultSet();
        $hydratorClass1 = $this->classMethodsHydratorClass;
        $hydratorClass2 = $this->arraySerializableHydratorClass;

        $hydrator1 = new $hydratorClass1();
        $hydrator2 = new $hydratorClass2();

        // First mutation
        $result = $hydratingRs->setHydrator($hydrator1);

        // Verify fluent interface
        self::assertSame($hydratingRs, $result);

        // Verify the first mutation occurred
        self::assertSame($hydrator1, $hydratingRs->getHydrator());

        // Second mutation to verify mutability
        $hydratingRs->setHydrator($hydrator2);

        // Verify the instance was actually mutated
        self::assertSame($hydrator2, $hydratingRs->getHydrator());
        self::assertNotSame($hydrator1, $hydratingRs->getHydrator());
    }

    public function testGetHydrator(): void
    {
        $hydratingRs = new HydratingResultSet();
        // Verify getHydrator() returns default ArraySerializable hydrator
        self::assertInstanceOf($this->arraySerializableHydratorClass, $hydratingRs->getHydrator());
    }

    /**
     * @throws Exception
     */
    public function testCurrentHasData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);
        // Verify current() returns hydrated object when data exists
        $obj = $hydratingRs->current();
        self::assertInstanceOf('ArrayObject', $obj);
    }

    /**
     * @throws Exception
     */
    public function testCurrentDoesnotHasData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([]);

        // Verify current() returns null when no data exists
        $result = $hydratingRs->current();
        self::assertNull($result);
    }

    /**
     * @throws Exception
     * @todo Implement testToArray().
     */
    public function testToArray(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);
        // Verify toArray() returns array of hydrated objects
        $obj = $hydratingRs->toArray();
        self::assertIsArray($obj);
    }

    public function testConstructorDefaultsToArraySerializableHydrator(): void
    {
        $hydratingRs = new HydratingResultSet();

        self::assertInstanceOf(ArraySerializableHydrator::class, $hydratingRs->getHydrator());
    }

    public function testSetRowPrototypeStoresPrototype(): void
    {
        $hydratingRs = new HydratingResultSet();
        $prototype   = new stdClass();

        $result = $hydratingRs->setRowPrototype($prototype);

        self::assertSame($hydratingRs, $result);
        self::assertSame($prototype, $hydratingRs->getRowPrototype());
    }

    public function testGetRowPrototypeReturnsDefaultArrayObject(): void
    {
        $hydratingRs = new HydratingResultSet();

        self::assertInstanceOf(ArrayObject::class, $hydratingRs->getRowPrototype());
    }

    public function testCurrentWithBufferReturnsBufferedObject(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $hydratingRs->buffer();

        $first = $hydratingRs->current();
        $hydratingRs->rewind();
        $buffered = $hydratingRs->current();

        self::assertSame($first, $buffered);
    }

    public function testToArrayUsesHydratorExtract(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);

        $result = $hydratingRs->toArray();

        self::assertCount(1, $result);
        self::assertArrayHasKey('id', $result[0]);
        self::assertSame(1, $result[0]['id']);
    }

    public function testCurrentDisablesBufferingImplicitly(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1],
        ]));

        $hydratingRs->current();

        $this->expectException(RuntimeException::class);
        $hydratingRs->buffer();
    }
}
