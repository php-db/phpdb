<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Index;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Index\Index;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Index::class, '__construct')]
#[CoversMethod(Index::class, 'getExpressionData')]
final class IndexTest extends TestCase
{
    private function compareExpressionTypeValues(array $expressionValues, array $comparisonValues): void
    {
        $diff = array_udiff($expressionValues, $comparisonValues, function($a, $b): int {
            return [$a->getType(), $a->getValue()] <=> [$b->getType(), $b->getValue()];
        });

        var_dump($diff);
        exit;

        self::assertCount(0, $diff);
    }

    public function testGetExpressionData(): void
    {
        $uk = new Index('foo', 'my_uk');

        $expressionData = $uk->getExpressionData();
        $expressionValues = $expressionData->getExpressionValues();
        $comparisonValues = [
            Argument::identifier('my_uk'),
            Argument::identifier('foo'),
        ];

        self::assertEquals('INDEX %s(%s)', $expressionData->getExpressionSpecification());
        self::assertTrue($this->compareExpressionTypeValues($expressionValues, $comparisonValues));
    }

    public function testGetExpressionDataWithLength(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10, 5]);

        $expressionData = $key->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s(5))', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('my_uk'),
            Argument::identifier('foo'),
            Argument::identifier('bar'),
        ], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithLengthUnmatched(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10]);

        $expressionData = $key->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s)', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('my_uk'),
            Argument::identifier('foo'),
            Argument::identifier('bar'),
        ], $expressionData->getExpressionValues());
    }
}
