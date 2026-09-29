<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Argument;

use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ArgumentType;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Literal::class, '__construct')]
#[CoversMethod(Literal::class, 'getType')]
#[CoversMethod(Literal::class, 'getValue')]
#[CoversMethod(Literal::class, 'getSpecification')]
final class LiteralTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function getSpecificationReturnsPlaceholder(): void
    {
        $literal = new Literal('test');

        static::assertSame('%s', $literal->getSpecification());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTypeReturnsLiteral(): void
    {
        $literal = new Literal('test');

        static::assertSame(ArgumentType::Literal, $literal->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValueReturnsLiteralString(): void
    {
        $literal = new Literal('NOW()');

        static::assertSame('NOW()', $literal->getValue());
    }
}
