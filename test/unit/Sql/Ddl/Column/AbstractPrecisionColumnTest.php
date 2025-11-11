<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getExpressionData')]
class AbstractPrecisionColumnTest extends TestCase
{
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
    public function testGetExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
            Argument::literal('10,5'),
        ], $expressionData->getExpressionValues());
    }
}
