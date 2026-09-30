<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\RowPrototypeInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversMethod(AbstractResultSet::class, 'rowToArray')]
#[CoversMethod(RowPrototypeResultSet::class, 'current')]
#[CoversMethod(RowPrototypeResultSet::class, 'toArray')]
#[Group('unit')]
final class RowPrototypeResultSetTest extends TestCase
{
    #[Test]
    public function currentPopulatesThePrototypeFromAnObjectRow(): void
    {
        $resultSet = new RowPrototypeResultSet($this->createRowPrototype());
        $row       = new stdClass();
        $row->id   = 1;
        $row->name = 'one';
        $resultSet->initialize(new ArrayIterator([$row]));

        $current = $resultSet->current();

        static::assertInstanceOf(RowPrototypeInterface::class, $current);
        static::assertSame(['id' => 1, 'name' => 'one'], $current->toArray());
    }

    #[Test]
    public function currentPopulatesThePrototypeFromARowCarryingItsValuesAsElements(): void
    {
        $resultSet = new RowPrototypeResultSet($this->createRowPrototype());
        $resultSet->initialize(new ArrayIterator([new ArrayObject(['id' => 1, 'name' => 'one'])]));

        $current = $resultSet->current();

        static::assertInstanceOf(RowPrototypeInterface::class, $current);
        static::assertSame(['id' => 1, 'name' => 'one'], $current->toArray());
    }

    #[Test]
    public function currentRejectsARowThatExposesNothing(): void
    {
        $resultSet = new RowPrototypeResultSet($this->createRowPrototype());
        $resultSet->initialize(new ArrayIterator([new stdClass()]));

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('exposes no properties');

        $resultSet->current();
    }

    #[Test]
    public function currentReturnsARowThatIsAlreadyAPrototypeUntouched(): void
    {
        $prototype = $this->createRowPrototype();
        $row       = $this->createRowPrototype()->populate(['id' => 1]);
        $resultSet = new RowPrototypeResultSet($prototype);
        $resultSet->initialize(new ArrayIterator([$row]));

        static::assertSame($row, $resultSet->current());
    }

    #[Test]
    public function currentReturnsDataUnchangedWhenNotArray(): void
    {
        $prototype = $this->createRowPrototype();
        $resultSet = new RowPrototypeResultSet($prototype);
        $resultSet->initialize(new ArrayIterator([null]));

        static::assertNull($resultSet->current());
    }

    #[Test]
    public function currentReturnsPopulatedCloneOfPrototypeForArrayRow(): void
    {
        $prototype = $this->createRowPrototype();
        $resultSet = new RowPrototypeResultSet($prototype);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
        ]));

        $current = $resultSet->current();

        static::assertInstanceOf(RowPrototypeInterface::class, $current);
        static::assertNotSame($prototype, $current);
        static::assertSame(['id' => 1, 'name' => 'one'], $current->toArray());
    }

    #[Test]
    public function toArrayConvertsPrototypeRowsToArrays(): void
    {
        $prototype = $this->createRowPrototype();
        $resultSet = new RowPrototypeResultSet($prototype);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1],
            ['id' => 2],
        ]));

        static::assertSame(
            [
                ['id' => 1],
                ['id' => 2],
            ],
            $resultSet->toArray(),
        );
    }

    private function createRowPrototype(): RowPrototypeInterface
    {
        return new class implements RowPrototypeInterface {
            private array $data = [];

            public function populate(array $data): RowPrototypeInterface
            {
                $this->data = $data;

                return $this;
            }

            public function toArray(): array
            {
                return $this->data;
            }
        };
    }
}
