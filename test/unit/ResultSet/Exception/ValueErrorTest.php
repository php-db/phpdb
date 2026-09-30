<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\ResultSet\Exception\ValueError;
use PhpDb\ResultSet\ObjectResultSet;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError as GlobalValueError;

#[Group('unit')]
#[CoversMethod(ValueError::class, 'forUnreadableRow')]
#[CoversMethod(ValueError::class, 'forRowThatIsNotAnObject')]
#[CoversMethod(ValueError::class, 'forRowThatIsNotArrayData')]
#[CoversMethod(ValueError::class, 'forRowThatIsNotArrayDataOrPrototype')]
#[CoversMethod(ValueError::class, 'forUnsupportedRow')]
final class ValueErrorTest extends TestCase
{
    #[Test]
    public function forRowThatIsNotAnObjectNamesTheAcceptedTypes(): void
    {
        self::assertStringContainsString(
            'which accepts an object',
            ValueError::forRowThatIsNotAnObject('array', ObjectResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataNamesTheAcceptedTypes(): void
    {
        self::assertStringContainsString(
            'which accepts an array or ArrayObject',
            ValueError::forRowThatIsNotArrayData('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataOrPrototypeNamesTheAcceptedTypes(): void
    {
        self::assertStringContainsString(
            'which accepts an array, ArrayObject or RowPrototypeInterface',
            ValueError::forRowThatIsNotArrayDataOrPrototype('stdClass', RowPrototypeResultSet::class)
                ->getMessage(),
        );
    }

    #[Test]
    public function forUnreadableRowNamesTheOffendingType(): void
    {
        self::assertStringContainsString(
            'A row of type "PDORow"',
            ValueError::forUnreadableRow('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forUnreadableRowReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(
            ExceptionInterface::class,
            ValueError::forUnreadableRow('PDORow', ResultSet::class),
        );
    }

    #[Test]
    public function forUnsupportedRowNamesTheOffendingType(): void
    {
        self::assertStringContainsString(
            'A row of type "PDORow"',
            ValueError::forRowThatIsNotArrayData('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forUnsupportedRowNamesTheResultSetThatRefusedIt(): void
    {
        self::assertStringContainsString(
            ResultSet::class,
            ValueError::forRowThatIsNotArrayData('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forUnsupportedRowRemainsCatchableAsAValueError(): void
    {
        self::assertInstanceOf(
            GlobalValueError::class,
            ValueError::forRowThatIsNotArrayData('bool', ResultSet::class),
        );
    }

    #[Test]
    public function forUnsupportedRowReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(
            ExceptionInterface::class,
            ValueError::forRowThatIsNotArrayData('bool', ResultSet::class),
        );
    }
}
