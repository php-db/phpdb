<?php

namespace PhpDbTest\ResultSet;

use PhpDb\ResultSet\HydratingResultSet;
use Laminas\Hydrator\ArraySerializable;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\ClassMethods;
use Laminas\Hydrator\ClassMethodsHydrator;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use stdClass;

use function class_exists;

#[CoversMethod(HydratingResultSet::class, 'setObjectPrototype')]
#[CoversMethod(HydratingResultSet::class, 'getObjectPrototype')]
#[CoversMethod(HydratingResultSet::class, 'setHydrator')]
#[CoversMethod(HydratingResultSet::class, 'getHydrator')]
#[CoversMethod(HydratingResultSet::class, 'current')]
#[CoversMethod(HydratingResultSet::class, 'toArray')]
final class HydratingResultSetTest extends TestCase
{
    private string $arraySerializableHydratorClass;

    private string $classMethodsHydratorClass;

    #[Override]
    protected function setUp(): void
    {
        $this->arraySerializableHydratorClass = class_exists(ArraySerializableHydrator::class)
            ? ArraySerializableHydrator::class
            : ArraySerializable::class;

        $this->classMethodsHydratorClass = class_exists(ClassMethodsHydrator::class)
            ? ClassMethodsHydrator::class
            : ClassMethods::class;
    }

    public function testSetObjectPrototype(): void
    {
        $prototype   = new stdClass();
        $hydratingRs = new HydratingResultSet();
        self::assertSame($hydratingRs, $hydratingRs->setObjectPrototype($prototype));
    }

    public function testGetObjectPrototype(): void
    {
        $hydratingRs = new HydratingResultSet();
        self::assertInstanceOf('ArrayObject', $hydratingRs->getObjectPrototype());
    }

    public function testSetHydrator(): void
    {
        $hydratingRs   = new HydratingResultSet();
        $hydratorClass = $this->classMethodsHydratorClass;
        self::assertSame($hydratingRs, $hydratingRs->setHydrator(new $hydratorClass()));
    }

    public function testGetHydrator(): void
    {
        $hydratingRs = new HydratingResultSet();
        self::assertInstanceOf($this->arraySerializableHydratorClass, $hydratingRs->getHydrator());
    }

    public function testCurrentHasData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);
        $obj = $hydratingRs->current();
        self::assertInstanceOf('ArrayObject', $obj);
    }

    public function testCurrentDoesnotHasData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([]);
        $result = $hydratingRs->current();
        self::assertNull($result);
    }

    /**
     * @todo Implement testToArray().
     */
    public function testToArray(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);
        $obj = $hydratingRs->toArray();
        self::assertIsArray($obj);
    }
}
