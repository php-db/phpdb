<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\RowBuffer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(RowBuffer::class, 'clear')]
#[CoversMethod(RowBuffer::class, 'get')]
#[CoversMethod(RowBuffer::class, 'has')]
#[CoversMethod(RowBuffer::class, 'isBuffered')]
#[CoversMethod(RowBuffer::class, 'isStoring')]
#[CoversMethod(RowBuffer::class, 'isPending')]
#[CoversMethod(RowBuffer::class, 'put')]
#[CoversMethod(RowBuffer::class, 'startIteration')]
#[CoversMethod(RowBuffer::class, 'store')]
#[CoversMethod(RowBuffer::class, 'useDataSource')]
#[Group('unit')]
final class RowBufferTest extends TestCase
{
    #[Test]
    public function aFreshBufferIsPending(): void
    {
        $buffer = new RowBuffer();

        static::assertTrue($buffer->isPending());
        static::assertFalse($buffer->isStoring());
        static::assertFalse($buffer->isBuffered());
    }

    #[Test]
    public function clearForgetsTheRowsItHeld(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();
        $buffer->put(0, ['id' => 1]);

        $buffer->clear();

        static::assertFalse($buffer->has(0));
    }

    #[Test]
    public function clearLeavesTheStateAlone(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();

        $buffer->clear();

        static::assertTrue($buffer->isStoring());
    }

    #[Test]
    public function getReturnsNullForAPositionItHoldsNothingFor(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();

        static::assertNull($buffer->get(3));
    }

    #[Test]
    public function hasIsFalseWhileNotStoringEvenAfterAPut(): void
    {
        $buffer = new RowBuffer();
        $buffer->useDataSource();
        $buffer->put(0, ['id' => 1]);

        static::assertFalse($buffer->has(0));
    }

    #[Test]
    public function iterationCannotBeginAndThenBeBuffered(): void
    {
        $buffer = new RowBuffer();
        $buffer->startIteration();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNBUFFERED_ITERATION);

        $buffer->store();
    }

    #[Test]
    public function iterationLeavesAStoringBufferStoring(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();

        $buffer->startIteration();

        static::assertTrue($buffer->isStoring());
    }

    #[Test]
    public function storeHoldsTheRowsPutIntoIt(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();

        $buffer->put(0, ['id' => 1]);

        static::assertTrue($buffer->has(0));
        static::assertSame(['id' => 1], $buffer->get(0));
    }

    #[Test]
    public function storeLeavesAPassthroughBufferDeferringToItsDataSource(): void
    {
        $buffer = new RowBuffer();
        $buffer->useDataSource();

        $buffer->store();

        static::assertFalse($buffer->isStoring());
        static::assertTrue($buffer->isBuffered());
    }

    #[Test]
    public function storeMakesTheBufferHoldRows(): void
    {
        $buffer = new RowBuffer();

        $buffer->store();

        static::assertTrue($buffer->isStoring());
        static::assertTrue($buffer->isBuffered());
    }

    #[Test]
    public function storingTwiceKeepsTheRowsAlreadyHeld(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();
        $buffer->put(0, ['id' => 1]);

        $buffer->store();

        static::assertSame(['id' => 1], $buffer->get(0));
    }

    #[Test]
    public function useDataSourceCountsAsBufferedWithoutStoring(): void
    {
        $buffer = new RowBuffer();

        $buffer->useDataSource();

        static::assertTrue($buffer->isBuffered());
        static::assertFalse($buffer->isStoring());
        static::assertFalse($buffer->isPending());
    }

    #[Test]
    public function useDataSourceDropsTheRowsItWasHolding(): void
    {
        $buffer = new RowBuffer();
        $buffer->store();
        $buffer->put(0, ['id' => 1]);

        $buffer->useDataSource();

        static::assertNull($buffer->get(0));
    }
}
