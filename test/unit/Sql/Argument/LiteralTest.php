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
    public function testGetTypeReturnsLiteral(): void
    {
        $literal = new Literal('test');

        self::assertSame(ArgumentType::Literal, $literal->getType());
    }

    public function testGetValueReturnsLiteralString(): void
    {
        $literal = new Literal('NOW()');

        self::assertSame('NOW()', $literal->getValue());
    }

    public function testGetSpecificationReturnsPlaceholder(): void
    {
        $literal = new Literal('test');

        self::assertSame('%s', $literal->getSpecification());
    }
}
