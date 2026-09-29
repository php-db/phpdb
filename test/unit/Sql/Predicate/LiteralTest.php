<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Predicate\Literal;
use PHPUnit\Framework\TestCase;

class LiteralTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionData(): void
    {
        $literal = new Literal('bar');

        $expressionData = $literal->getExpressionData();

        static::assertSame('bar', $expressionData['spec']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getLiteral(): void
    {
        $literal = new Literal('bar');
        static::assertSame('bar', $literal->getLiteral());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setLiteral(): void
    {
        $literal = new Literal('bar');

        // First mutation
        $result = $literal->setLiteral('foo');

        // Verify fluent interface
        static::assertSame($literal, $result);

        // Verify the first mutation occurred
        static::assertSame('foo', $literal->getLiteral());

        // Second mutation to verify mutability
        $literal->setLiteral('baz');

        // Verify the instance was actually mutated
        static::assertSame('baz', $literal->getLiteral());
    }
}
