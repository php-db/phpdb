<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Binary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Binary::class, 'getExpressionData')]
final class BinaryTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Binary('foo', 10000000);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('BINARY'),
            Argument::literal('10000000'),
        ], $expressionData->getExpressionValues());
    }
}
