<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varchar::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, '__construct')]
#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class VarcharTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function rendersLengthBeforeNullabilityAndDefault(): void
    {
        static::assertColumnRenders('"name" VARCHAR(20) NULL DEFAULT NULL', new Varchar('name', 20, true));
    }

    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        static::assertColumnRenders('"name" VARCHAR(20) NOT NULL', new Varchar('name', 20));
    }

    public function testGetExpressionData(): void
    {
        $column = new Varchar('foo', 20);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARCHAR'),
                Argument::literal('20'),
            ],
            $expressionData['values'],
        );

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL DEFAULT %s', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARCHAR'),
                Argument::literal('20'),
                Argument::value('bar'),
            ],
            $expressionData['values'],
        );
    }

    public function testInheritanceFromAbstractLengthColumn(): void
    {
        $column = new Varchar('test');
        self::assertInstanceOf(AbstractLengthColumn::class, $column);
    }

    public function testSetLengthAndGetLength(): void
    {
        $column = new Varchar('name');

        $result = $column->setLength(100);
        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(100, $column->getLength());
    }

    #[Test]
    #[DataProvider('missingLengthProvider')]
    public function throwsWhenRenderedWithoutLength(?int $length): void
    {
        $column = new Varchar('name', $length);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "name" of type VARCHAR requires a length');

        $column->getExpressionData();
    }
}
