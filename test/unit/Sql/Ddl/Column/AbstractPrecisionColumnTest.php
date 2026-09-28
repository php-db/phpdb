<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getExpressionData')]
#[Group('unit')]
final class AbstractPrecisionColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getExpressionDataOmitsLengthPlaceholderWhenPrecisionIsNotSet(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo'])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getExpressionDataUsesDigitsOnlyWhenDecimalIsNotSet(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
                Argument::literal('10'),
            ],
            $expressionData['values'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetDecimal(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(5, $column->getDecimal());
    }

    /**
     * @throws Exception
     */
    public function testGetDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(10, $column->getDigits());
    }

    /**
     * @throws Exception
     */
    public function testGetExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    /**
     * @throws Exception
     */
    public function testSetDecimal(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(5, $column->getDecimal());
        self::assertSame($column, $column->setDecimal(2));
        self::assertEquals(2, $column->getDecimal());
    }

    /**
     * @throws Exception
     */
    public function testSetDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(10, $column->getDigits());
        self::assertSame($column, $column->setDigits(12));
        self::assertEquals(12, $column->getDigits());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function throwsWhenDecimalIsSetWithoutDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', null, 2])
            ->onlyMethods([])
            ->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "foo" of type INTEGER has a decimal scale but no digits');

        $column->getExpressionData();
    }
}
