<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Predicate\Literal;
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
        $literal = new Literal('bar');

        $expressionData = $literal->getExpressionData();

        self::assertEquals('bar', $expressionData->getExpressionSpecification());
    }
}
