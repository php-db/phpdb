<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\ArrayResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversMethod(AbstractResultSet::class, 'rowToArray')]
#[CoversMethod(ArrayResultSet::class, 'current')]
#[CoversMethod(ArrayResultSet::class, 'toArray')]
#[Group('unit')]
final class ArrayResultSetTest extends TestCase
{
    #[Test]
    public function currentReducesAnObjectRowToAnArray(): void
    {
        $row       = new stdClass();
        $row->id   = 1;
        $row->name = 'one';

        $resultSet = new ArrayResultSet();
        $resultSet->initialize(new ArrayIterator([$row]));

        static::assertSame(['id' => 1, 'name' => 'one'], $resultSet->current());
    }

    #[Test]
    public function currentReducesARowCarryingItsValuesAsElements(): void
    {
        $resultSet = new ArrayResultSet();
        $resultSet->initialize(new ArrayIterator([new ArrayObject(['id' => 1, 'name' => 'one'])]));

        static::assertSame(['id' => 1, 'name' => 'one'], $resultSet->current());
    }

    #[Test]
    public function currentRejectsARowThatExposesNothing(): void
    {
        $resultSet = new ArrayResultSet();
        $resultSet->initialize(new ArrayIterator([new stdClass()]));

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('exposes no properties');

        $resultSet->current();
    }

    #[Test]
    public function currentReturnsAnArrayRowUntouched(): void
    {
        $resultSet = new ArrayResultSet();
        $resultSet->initialize([['id' => 1, 'name' => 'one']]);

        static::assertSame(['id' => 1, 'name' => 'one'], $resultSet->current());
    }

    #[Test]
    public function currentReturnsNullOnceTheRowsAreExhausted(): void
    {
        $resultSet = new ArrayResultSet();
        $resultSet->initialize(new ArrayIterator([]));

        static::assertNull($resultSet->current());
    }

    #[Test]
    public function toArrayReturnsRowsAsProvided(): void
    {
        $resultSet = new ArrayResultSet();
        $resultSet->initialize([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]);

        static::assertSame(
            [
                ['id' => 1, 'name' => 'one'],
                ['id' => 2, 'name' => 'two'],
            ],
            $resultSet->toArray(),
        );
    }
}
