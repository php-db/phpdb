<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Constraint\Check;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, 'getExpressionData')]
final class CheckTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $check = new Check('id>0', 'foo');

        $expressionData = $check->getExpressionData();

        self::assertEquals('CONSTRAINT %s CHECK (%s)', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('id>0')
        ], $expressionData->getExpressionValues());
    }
}
