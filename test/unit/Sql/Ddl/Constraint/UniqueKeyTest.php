<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Constraint\UniqueKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(UniqueKey::class, 'getExpressionData')]
final class UniqueKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new UniqueKey('foo', 'my_uk');

        $expressionData = $uk->getExpressionData();

        self::assertEquals('CONSTRAINT %s UNIQUE (%s)', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('my_uk'),
            Argument::identifier('foo'),
        ], $expressionData->getExpressionValues());
    }
}
