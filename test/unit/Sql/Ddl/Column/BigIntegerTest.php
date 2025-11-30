<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\BigInteger;
use PhpDb\Sql\Ddl\Column\Column;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(BigInteger::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class BigIntegerTest extends TestCase
{
    public function testObjectConstruction(): void
    {
        $integer = new BigInteger('foo');
        self::assertEquals('foo', $integer->getName());
    }

    public function testGetExpressionData(): void
    {
        $column         = new BigInteger('foo');
        $expressionData = $column->getExpressionData();

        self::assertEquals(
            '%s %s NOT NULL',
            $expressionData['spec']
        );

        self::assertEquals(
            [
                Argument::Identifier('foo'),
                Argument::Literal('BIGINT'),
            ],
            $expressionData['values']
        );
    }
}
