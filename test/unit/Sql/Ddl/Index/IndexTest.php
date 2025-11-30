<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Index;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Ddl\Index\Index;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Index::class, '__construct')]
#[CoversMethod(Index::class, 'getExpressionData')]
final class IndexTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new Index('foo', 'my_uk');

        $expressionData = $uk->getExpressionData();

        self::assertEquals('INDEX %s(%s)', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_uk'),
            new Identifier('foo'),
        ], $expressionData['values']);
    }

    public function testGetExpressionDataWithLength(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10, 5]);

        $expressionData = $key->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s(5))', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_uk'),
            new Identifier('foo'),
            new Identifier('bar'),
        ], $expressionData['values']);
    }

    public function testGetExpressionDataWithLengthUnmatched(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10]);

        $expressionData = $key->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s)', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_uk'),
            new Identifier('foo'),
            Argument::identifier('bar'),
        ], $expressionData['values']);
    }
}
