<?php

namespace PhpDbTest\Sql;

use PhpDb\Sql\Literal;
use PHPUnit\Framework\TestCase;

class LiteralTest extends TestCase
{
    public function testSetLiteral(): void
    {
        $literal = new Literal('bar');
        self::assertSame($literal, $literal->setLiteral('foo'));
    }

    public function testGetLiteral(): void
    {
        $literal = new Literal('bar');
        self::assertEquals('bar', $literal->getLiteral());
    }

    public function testGetExpressionData(): void
    {
        $literal        = new Literal('bar');
        $expressionData = $literal->getExpressionData();

        self::assertEquals(
            'bar',
            $expressionData->getExpressionSpecification()
        );

        self::assertEquals(
            [],
            $expressionData->getExpressionValues()
        );
    }

    public function testGetExpressionDataWillEscapePercent(): void
    {
        $literal        = new Literal('X LIKE "foo%"');
        $expressionData = $literal->getExpressionData();

        self::assertEquals(
            'X LIKE "foo%%"',
            $expressionData->getExpressionSpecification()
        );

        self::assertEquals(
            [],
            $expressionData->getExpressionValues()
        );
    }
}
