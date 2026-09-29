<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Index;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Index\Index;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Index::class, '__construct')]
#[CoversMethod(Index::class, 'setType')]
#[CoversMethod(Index::class, 'getType')]
#[CoversMethod(Index::class, 'getExpressionData')]
final class IndexTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $uk = new Index('foo', 'my_uk');

        $expressionData = $uk->getExpressionData();

        static::assertSame('INDEX %s(%s)', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('my_uk'),
                new Identifier('foo'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithBtreeType(): void
    {
        $index = new Index('foo', 'my_idx');
        $index->setType('BTREE');

        $expressionData = $index->getExpressionData();

        static::assertSame('INDEX %s(%s) USING %s', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('my_idx'),
                new Identifier('foo'),
                new Literal('BTREE'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithHashType(): void
    {
        $index = new Index('foo', 'my_idx');
        $index->setType('HASH');

        $expressionData = $index->getExpressionData();

        static::assertSame('INDEX %s(%s) USING %s', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('my_idx'),
                new Identifier('foo'),
                new Literal('HASH'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithLength(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10, 5]);

        $expressionData = $key->getExpressionData();

        static::assertSame('INDEX %s(%s(10), %s(5))', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('my_uk'),
                new Identifier('foo'),
                new Identifier('bar'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithLengthUnmatched(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10]);

        $expressionData = $key->getExpressionData();

        static::assertSame('INDEX %s(%s(10), %s)', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('my_uk'),
                new Identifier('foo'),
                new Identifier('bar'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithTypeAndLengths(): void
    {
        $index = new Index(['foo', 'bar'], 'my_idx', [10, 5]);
        $index->setType('BTREE');

        $expressionData = $index->getExpressionData();

        static::assertSame('INDEX %s(%s(10), %s(5)) USING %s', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('my_idx'),
                new Identifier('foo'),
                new Identifier('bar'),
                new Literal('BTREE'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function setTypeAndGetType(): void
    {
        $index = new Index('foo', 'my_idx');
        static::assertNull($index->getType());

        $result = $index->setType('BTREE');
        static::assertSame($index, $result);
        static::assertSame('BTREE', $index->getType());
    }
}
