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

#[CoversMethod(Varbinary::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class VarbinaryTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        static::assertColumnRenders('"data" VARBINARY(20) NOT NULL', new Varbinary('data', 20));
    }

    public function testGetExpressionData(): void
    {
        $column = new Varbinary('foo', 20);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARBINARY'),
                Argument::literal('20'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    #[DataProvider('missingLengthProvider')]
    public function throwsWhenRenderedWithoutLength(?int $length): void
    {
        $column = new Varbinary('data', $length);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "data" of type VARBINARY requires a length');

        $column->getExpressionData();
    }
}
