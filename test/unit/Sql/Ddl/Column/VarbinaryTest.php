<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Varbinary;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(Varbinary::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class VarbinaryTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionData(): void
    {
        $column = new Varbinary('foo', 20);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARBINARY'),
                Argument::literal('20'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        static::assertColumnRenders('"data" VARBINARY(20) NOT NULL', new Varbinary('data', 20));
    }

    #[Test]
    #[DataProvider('missingLengthProvider')]
    public function throwsWhenRenderedWithoutLength(?int $length): void
    {
        $column = new Varbinary('data', $length);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf(InvalidArgumentException::MISSING_COLUMN_LENGTH, 'data', 'VARBINARY'));

        $column->getExpressionData();
    }
}
