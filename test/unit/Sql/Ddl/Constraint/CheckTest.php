<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Constraint\Check;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, '__construct')]
#[CoversMethod(Check::class, 'getExpressionData')]
final class CheckTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $check = new Check('id>0', 'foo');

        $expressionData = $check->getExpressionData();

        self::assertEquals('CONSTRAINT %s CHECK (%s)', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('id>0'),
        ], $expressionData['values']);
    }
}
