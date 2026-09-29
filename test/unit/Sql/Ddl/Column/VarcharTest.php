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

use function sprintf;

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionData(): void
    {
        $column = new Varchar('foo', 20);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARCHAR'),
                Argument::literal('20'),
            ],
            $expressionData['values'],
        );

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARCHAR'),
                Argument::literal('20'),
                Argument::value('bar'),
            ],
            $expressionData['values'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inheritanceFromAbstractLengthColumn(): void
    {
        $column = new Varchar('test');
        static::assertInstanceOf(AbstractLengthColumn::class, $column);
    }

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function setLengthAndGetLength(): void
    {
        $column = new Varchar('name');

        $result = $column->setLength(100);
        static::assertSame($column, $result); // Fluent interface
        static::assertSame(100, $column->getLength());
    }

    #[Test]
    #[DataProvider('missingLengthProvider')]
    public function throwsWhenRenderedWithoutLength(?int $length): void
    {
        $column = new Varchar('name', $length);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf(InvalidArgumentException::MISSING_COLUMN_LENGTH, 'name', 'VARCHAR'));

        $column->getExpressionData();
    }
}
