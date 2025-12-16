<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Literal;
use PHPUnit\Framework\TestCase;

class LiteralTest extends TestCase
{
    public function testSetLiteral(): void
    {
        $literal = new Literal('bar');

        // First mutation
        $result = $literal->setLiteral('foo');

        // Verify fluent interface
        self::assertSame($literal, $result);

        // Verify the first mutation occurred
        self::assertEquals('foo', $literal->getLiteral());

        // Second mutation to verify mutability
        $literal->setLiteral('baz');

        // Verify the instance was actually mutated
        self::assertEquals('baz', $literal->getLiteral());
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
            $expressionData['spec']
        );

        self::assertEquals(
            [],
            $expressionData['values']
        );
    }

    public function testGetExpressionDataReturnsSpecDirectly(): void
    {
        $literal        = new Literal('X LIKE "foo%"');
        $expressionData = $literal->getExpressionData();

        // Literal returns spec as-is (no escaping needed since vsprintf is not used)
        self::assertEquals(
            'X LIKE "foo%"',
            $expressionData['spec']
        );

        self::assertEquals(
            [],
            $expressionData['values']
        );
    }
}
