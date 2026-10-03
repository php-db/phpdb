<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet\Exception;

use Exception;
use PhpDb\Exception\ExceptionInterface;
use PhpDb\ResultSet\Exception\UnexpectedValueException;
use PhpDb\ResultSet\ObjectResultSet;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(UnexpectedValueException::class, 'forUnreadableRow')]
#[CoversMethod(UnexpectedValueException::class, 'forRowThatIsNotAnObject')]
#[CoversMethod(UnexpectedValueException::class, 'forRowThatIsNotArrayData')]
#[CoversMethod(UnexpectedValueException::class, 'forRowThatIsNotArrayDataOrPrototype')]
final class UnexpectedValueExceptionTest extends TestCase
{
    #[Test]
    public function forRowThatIsNotAnObjectNamesTheAcceptedTypes(): void
    {
        self::assertStringContainsString(
            'which accepts an object',
            UnexpectedValueException::forRowThatIsNotAnObject('array', ObjectResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataIsCatchableAsAnException(): void
    {
        self::assertInstanceOf(
            Exception::class,
            UnexpectedValueException::forRowThatIsNotArrayData('bool', ResultSet::class),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataNamesTheAcceptedTypes(): void
    {
        self::assertStringContainsString(
            'which accepts an array or ArrayObject',
            UnexpectedValueException::forRowThatIsNotArrayData('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataNamesTheOffendingType(): void
    {
        self::assertStringContainsString(
            'A row of type "PDORow"',
            UnexpectedValueException::forRowThatIsNotArrayData('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataNamesTheResultSetThatRefusedIt(): void
    {
        self::assertStringContainsString(
            ResultSet::class,
            UnexpectedValueException::forRowThatIsNotArrayData('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataOrPrototypeNamesTheAcceptedTypes(): void
    {
        self::assertStringContainsString(
            'which accepts an array, ArrayObject or RowPrototypeInterface',
            UnexpectedValueException::forRowThatIsNotArrayDataOrPrototype('stdClass', RowPrototypeResultSet::class)
                ->getMessage(),
        );
    }

    #[Test]
    public function forRowThatIsNotArrayDataReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(
            ExceptionInterface::class,
            UnexpectedValueException::forRowThatIsNotArrayData('bool', ResultSet::class),
        );
    }

    #[Test]
    public function forUnreadableRowNamesTheOffendingType(): void
    {
        self::assertStringContainsString(
            'A row of type "PDORow"',
            UnexpectedValueException::forUnreadableRow('PDORow', ResultSet::class)->getMessage(),
        );
    }

    #[Test]
    public function forUnreadableRowReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(
            ExceptionInterface::class,
            UnexpectedValueException::forUnreadableRow('PDORow', ResultSet::class),
        );
    }
}
