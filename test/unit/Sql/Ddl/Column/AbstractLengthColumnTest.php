<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class AbstractLengthColumnTest extends TestCase
{
    use ColumnAssertionsTrait;

    /**
     * @throws Exception
     */
    #[Test]
    public function getExpressionDataOmitsLengthPlaceholderWhenLengthIsNotSet(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo'])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Literal('INTEGER'),
            ],
            $expressionData['values'],
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 4, true])
            ->onlyMethods([])
            ->getMock();

        static::assertColumnRenders('"foo" INTEGER(4) NULL DEFAULT NULL', $column);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function rendersWithoutParenthesesWhenLengthIsNotSet(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo'])
            ->onlyMethods([])
            ->getMock();

        static::assertColumnRenders('"foo" INTEGER NOT NULL', $column);
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

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                new Identifier('foo'),
                new Literal('INTEGER'),
                new Literal('4'),
            ],
            $expressionData['values'],
        );
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
}
