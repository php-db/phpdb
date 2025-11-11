<?php

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
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
