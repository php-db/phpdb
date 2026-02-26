<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(SmallInteger::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class SmallIntegerTest extends TestCase
{
    public function testObjectConstruction(): void
    {
        $integer = new SmallInteger('foo');
        self::assertEquals('foo', $integer->getName());
    }

    public function testGetExpressionData(): void
    {
        $column         = new SmallInteger('foo');
        $expressionData = $column->getExpressionData();

        self::assertEquals(
            '%s %s NOT NULL',
            $expressionData['spec']
        );

        self::assertEquals(
            [
                Argument::Identifier('foo'),
                Argument::Literal('SMALLINT'),
            ],
            $expressionData['values']
        );
    }
}
