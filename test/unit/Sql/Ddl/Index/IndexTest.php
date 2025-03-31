<?php

namespace LaminasTest\Db\Sql\Ddl\Index;

use Laminas\Db\Sql\Ddl\Index\Index;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Index::class, 'getExpressionData')]
final class IndexTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new Index('foo', 'my_uk');
        self::assertEquals(
            [
                [
                    'INDEX %s(%s)',
                    ['my_uk', 'foo'],
                    [$uk::TYPE_IDENTIFIER, $uk::TYPE_IDENTIFIER],
                ],
            ],
            $uk->getExpressionData()
        );
    }

    public function testGetExpressionDataWithLength(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10, 5]);
        self::assertEquals(
            [
                [
                    'INDEX %s(%s(10), %s(5))',
                    ['my_uk', 'foo', 'bar'],
                    [$key::TYPE_IDENTIFIER, $key::TYPE_IDENTIFIER, $key::TYPE_IDENTIFIER],
                ],
            ],
            $key->getExpressionData()
        );
    }

    public function testGetExpressionDataWithLengthUnmatched(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10]);
        self::assertEquals(
            [
                [
                    'INDEX %s(%s(10), %s)',
                    ['my_uk', 'foo', 'bar'],
                    [$key::TYPE_IDENTIFIER, $key::TYPE_IDENTIFIER, $key::TYPE_IDENTIFIER],
                ],
            ],
            $key->getExpressionData()
        );
    }
}
