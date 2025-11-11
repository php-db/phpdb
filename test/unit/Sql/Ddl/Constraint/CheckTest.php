<?php

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\Check;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, 'getExpressionData')]
final class CheckTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $check = new Check('id>0', 'foo');
        self::assertEquals(
            [
                [
                    'CONSTRAINT %s CHECK (%s)',
                    ['foo', 'id>0'],
                    [$check::TYPE_IDENTIFIER, $check::TYPE_LITERAL],
                ],
            ],
            $check->getExpressionData()
        );
    }
}
