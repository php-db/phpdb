<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
class AbstractLengthColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSetLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 55])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(55, $column->getLength());
        self::assertSame($column, $column->setLength(20));
        self::assertEquals(20, $column->getLength());
    }

    /**
     * @throws Exception
     */
    public function testGetLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 55])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(55, $column->getLength());
    }

    /**
     * @throws Exception
     */
    public function testGetExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 4])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
            Argument::literal('4'),
        ], $expressionData->getExpressionValues());
    }
}
