<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use Exception;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Override;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\Exception\ValueError;
use PhpDb\ResultSet\HydratingResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversMethod(AbstractResultSet::class, 'getArrayData')]
#[CoversMethod(AbstractResultSet::class, 'unsupportedRowError')]
#[CoversMethod(HydratingResultSet::class, 'setObjectPrototype')]
#[CoversMethod(HydratingResultSet::class, 'getObjectPrototype')]
#[CoversMethod(HydratingResultSet::class, 'setHydrator')]
#[CoversMethod(HydratingResultSet::class, 'getHydrator')]
#[CoversMethod(HydratingResultSet::class, 'current')]
#[CoversMethod(HydratingResultSet::class, 'mapRow')]
#[CoversMethod(HydratingResultSet::class, 'toArray')]
#[CoversMethod(HydratingResultSet::class, '__construct')]
#[CoversMethod(HydratingResultSet::class, 'setRowPrototype')]
#[CoversMethod(HydratingResultSet::class, 'getRowPrototype')]
#[CoversMethod(AbstractResultSet::class, 'currentRow')]
#[Group('unit')]
final class HydratingResultSetTest extends TestCase
{
    private string $arraySerializableHydratorClass;

    private string $classMethodsHydratorClass;

    #[Test]
    public function aBufferedReadPastTheLastRowReturnsNull(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([['id' => 1]]));
        $hydratingRs->buffer();
        $hydratingRs->current();
        $hydratingRs->next();

        static::assertNull($hydratingRs->current());
    }

    #[Test]
    public function aChangeToABufferedEntityCarriesIntoTheNextPass(): void
    {
        $hydratingRs = new HydratingResultSet(null, new ArrayObject([], ArrayObject::ARRAY_AS_PROPS));
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $hydratingRs->buffer();

        foreach ($hydratingRs as $row) {
            $row['name'] = 'changed';
        }

        $names = [];
        foreach ($hydratingRs as $row) {
            $names[] = $row['name'];
        }

        static::assertSame(['changed', 'changed'], $names);
    }

    #[Test]
    public function aNewHydratorOrPrototypeIsUsedForTheNextDataSource(): void
    {
        $hydratingRs = new HydratingResultSet(null, new ArrayObject());
        $hydratingRs->initialize(new ArrayIterator([['id' => 1]]));
        static::assertInstanceOf(ArrayObject::class, $hydratingRs->current());

        $hydratingRs->setRowPrototype(new stdClass());
        $hydratingRs->setHydrator(new ObjectPropertyHydrator());
        $hydratingRs->initialize(new ArrayIterator([['id' => 2]]));

        $row = $hydratingRs->current();
        static::assertInstanceOf(stdClass::class, $row);
        static::assertSame(2, $row->id);
    }

    #[Test]
    public function aSecondBufferedPassReturnsTheSameObject(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $hydratingRs->buffer();

        $first = $hydratingRs->current();
        $hydratingRs->rewind();

        static::assertSame($first, $hydratingRs->current());
    }

    #[Test]
    public function constructorDefaultsToArraySerializableHydrator(): void
    {
        $hydratingRs = new HydratingResultSet();

        static::assertInstanceOf(ArraySerializableHydrator::class, $hydratingRs->getHydrator());
    }

    #[Test]
    public function currentDisablesBufferingImplicitly(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1],
        ]));

        $hydratingRs->current();

        $this->expectException(RuntimeException::class);
        $hydratingRs->buffer();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function currentDoesnotHasData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([]);

        // Verify current() returns null when no data exists
        $result = $hydratingRs->current();
        static::assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function currentHasData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);
        // Verify current() returns hydrated object when data exists
        $obj = $hydratingRs->current();
        static::assertInstanceOf('ArrayObject', $obj);
    }

    #[Test]
    public function currentRejectsAnObjectRowRatherThanHydratingIt(): void
    {
        $row       = new stdClass();
        $row->id   = 1;
        $row->name = 'one';

        $resultSet = new HydratingResultSet(new ArraySerializableHydrator(), new ArrayObject());
        $resultSet->initialize(new ArrayIterator([$row]));

        self::expectException(ValueError::class);
        self::expectExceptionMessage('A row of type "stdClass"');

        $resultSet->current();
    }

    #[Test]
    public function currentRejectsARowThatExposesNothing(): void
    {
        $resultSet = new HydratingResultSet(new ArraySerializableHydrator(), new ArrayObject());
        $resultSet->initialize(new ArrayIterator([new stdClass()]));

        self::expectException(ValueError::class);
        self::expectExceptionMessage('will not transform a row it did not create');

        $resultSet->current();
    }

    #[Test]
    public function getHydrator(): void
    {
        $hydratingRs = new HydratingResultSet();
        // Verify getHydrator() returns default ArraySerializable hydrator
        static::assertInstanceOf($this->arraySerializableHydratorClass, $hydratingRs->getHydrator());
    }

    #[Test]
    public function getObjectPrototype(): void
    {
        $hydratingRs = new HydratingResultSet();
        // Verify getObjectPrototype() returns default ArrayObject prototype
        static::assertInstanceOf('ArrayObject', $hydratingRs->getObjectPrototype());
    }

    #[Test]
    public function getRowPrototypeReturnsDefaultArrayObject(): void
    {
        $hydratingRs = new HydratingResultSet();

        static::assertInstanceOf(ArrayObject::class, $hydratingRs->getRowPrototype());
    }

    #[Test]
    public function setHydrator(): void
    {
        $hydratingRs    = new HydratingResultSet();
        $hydratorClass1 = $this->classMethodsHydratorClass;
        $hydratorClass2 = $this->arraySerializableHydratorClass;

        $hydrator1 = new $hydratorClass1();
        $hydrator2 = new $hydratorClass2();

        // First mutation
        $result = $hydratingRs->setHydrator($hydrator1);

        // Verify fluent interface
        static::assertSame($hydratingRs, $result);

        // Verify the first mutation occurred
        static::assertSame($hydrator1, $hydratingRs->getHydrator());

        // Second mutation to verify mutability
        $hydratingRs->setHydrator($hydrator2);

        // Verify the instance was actually mutated
        static::assertSame($hydrator2, $hydratingRs->getHydrator());
        static::assertNotSame($hydrator1, $hydratingRs->getHydrator());
    }

    #[Test]
    public function setObjectPrototype(): void
    {
        $prototype1            = new stdClass();
        $prototype1->property1 = 'value1';
        $prototype2            = new stdClass();
        $prototype2->property2 = 'value2';
        $hydratingRs           = new HydratingResultSet();

        // First mutation
        $result = $hydratingRs->setObjectPrototype($prototype1);

        // Verify fluent interface
        static::assertSame($hydratingRs, $result);

        // Verify the first mutation occurred
        static::assertSame($prototype1, $hydratingRs->getObjectPrototype());

        // Second mutation to verify mutability
        $hydratingRs->setObjectPrototype($prototype2);

        // Verify the instance was actually mutated
        static::assertSame($prototype2, $hydratingRs->getObjectPrototype());
        static::assertNotSame($prototype1, $hydratingRs->getObjectPrototype());
    }

    #[Test]
    public function setRowPrototypeStoresPrototype(): void
    {
        $hydratingRs = new HydratingResultSet();
        $prototype   = new stdClass();

        $result = $hydratingRs->setRowPrototype($prototype);

        static::assertSame($hydratingRs, $result);
        static::assertSame($prototype, $hydratingRs->getRowPrototype());
    }

    #[Test]
    public function subclassGettersDecideHowRowsAreHydrated(): void
    {
        $hydratingRs = new class extends HydratingResultSet {
            public int $hydratorCalls = 0;

            #[Override]
            public function getHydrator(): ClassMethodsHydrator
            {
                $this->hydratorCalls++;

                return new ClassMethodsHydrator();
            }

            #[Override]
            public function getRowPrototype(): object
            {
                return new class {
                    public ?int $id = null;

                    public function setId(int $id): void
                    {
                        $this->id = $id * 10;
                    }
                };
            }
        };
        $hydratingRs->initialize(new ArrayIterator([['id' => 1], ['id' => 2]]));

        $ids = [];
        foreach ($hydratingRs as $row) {
            $ids[] = $row->id;
        }

        static::assertSame([10, 20], $ids);
        static::assertSame(1, $hydratingRs->hydratorCalls, 'getHydrator() is asked once per data source, not per row');
    }

    /**
     * @throws Exception
     * @todo Implement testToArray().
     */
    #[Test]
    public function toArray(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);
        // Verify toArray() returns array of hydrated objects
        $obj = $hydratingRs->toArray();
        static::assertIsArray($obj);
    }

    #[Test]
    public function toArrayReportsARowTheHydratorCannotExtract(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([1, 2]));

        self::expectException(ValueError::class);
        self::expectExceptionMessage('A row of type "int"');

        $hydratingRs->toArray();
    }

    #[Test]
    public function toArrayUsesHydratorExtract(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize([
            ['id' => 1, 'name' => 'one'],
        ]);

        $result = $hydratingRs->toArray();

        static::assertCount(1, $result);
        static::assertArrayHasKey('id', $result[0]);
        static::assertSame(1, $result[0]['id']);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->arraySerializableHydratorClass = ArraySerializableHydrator::class;
        $this->classMethodsHydratorClass      = ClassMethodsHydrator::class;
    }
}
