<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use PhpDb\ResultSet\Exception\UnexpectedValueException;
use PhpDb\ResultSet\ObjectResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function sprintf;

#[CoversMethod(ObjectResultSet::class, 'current')]
#[CoversMethod(ObjectResultSet::class, 'mapRow')]
#[CoversMethod(ObjectResultSet::class, 'rowToArray')]
#[CoversMethod(ObjectResultSet::class, 'toArray')]
#[Group('unit')]
final class ObjectResultSetTest extends TestCase
{
    /**
     * Types a fetch mode yielding rows other than objects would hand over.
     *
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function nonObjectRowProvider(): array
    {
        return [
            'an array row means the fetch mode yields arrays' => [['id' => 1], 'array'],
            'FETCH_BOUND yields a success flag'               => [true, 'bool'],
        ];
    }

    #[Test]
    #[DataProvider('nonObjectRowProvider')]
    public function currentRejectsARowThatIsNotAnObject(mixed $row, string $rowType): void
    {
        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([$row]));

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(sprintf('A row of type "%s"', $rowType));

        $resultSet->current();
    }

    #[Test]
    public function currentRepeatsTheSameInstanceWhileBuffered(): void
    {
        $row       = new stdClass();
        $row->id   = 1;
        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([$row]));
        $resultSet->buffer();

        $first = $resultSet->current();
        $resultSet->rewind();

        static::assertSame($first, $resultSet->current());
    }

    #[Test]
    public function currentReturnsNullOnceTheRowsAreExhausted(): void
    {
        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([]));

        static::assertNull($resultSet->current());
    }

    #[Test]
    public function currentReturnsTheVeryObjectTheDataSourceGave(): void
    {
        $row       = new stdClass();
        $row->id   = 1;
        $row->name = 'one';

        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([$row]));

        static::assertSame($row, $resultSet->current());
    }

    #[Test]
    public function currentSaysWhichTypesItAccepts(): void
    {
        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([['id' => 1]]));

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage('which accepts an object');

        $resultSet->current();
    }

    #[Test]
    public function toArrayReadsARowCarryingItsValuesAsElements(): void
    {
        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([new ArrayObject(['id' => 1, 'name' => 'one'])]));

        static::assertSame([['id' => 1, 'name' => 'one']], $resultSet->toArray());
    }

    #[Test]
    public function toArrayReadsThePublicPropertiesOfEachRow(): void
    {
        $first      = new stdClass();
        $first->id  = 1;
        $second     = new stdClass();
        $second->id = 2;

        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([$first, $second]));

        static::assertSame([['id' => 1], ['id' => 2]], $resultSet->toArray());
    }

    #[Test]
    public function toArrayRejectsARowThatExposesNothing(): void
    {
        $resultSet = new ObjectResultSet();
        $resultSet->initialize(new ArrayIterator([new stdClass()]));

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage('exposes no values');

        $resultSet->toArray();
    }
}
