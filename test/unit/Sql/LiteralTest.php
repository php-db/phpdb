<?php

namespace LaminasTest\Db\Sql;

use Laminas\Db\Sql\Literal;
use PHPUnit\Framework\TestCase;

final class LiteralTest extends TestCase
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
        $literal = new Literal('bar');
        self::assertEquals([['bar', [], []]], $literal->getExpressionData());
    }

    public function testGetExpressionDataWillEscapePercent(): void
    {
        $expression = new Literal('X LIKE "foo%"');
        self::assertEquals(
            [
                [
                    'X LIKE "foo%%"',
                    [],
                    [],
                ],
            ],
            $expression->getExpressionData()
        );
    }
}
