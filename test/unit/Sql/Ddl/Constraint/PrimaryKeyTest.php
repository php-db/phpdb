<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(PrimaryKey::class, 'getExpressionData')]
class PrimaryKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $pk = new PrimaryKey('foo');

        $expressionData = $pk->getExpressionData();

        self::assertEquals('PRIMARY KEY (%s)', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
        ], $expressionData->getExpressionValues());
    }
}
