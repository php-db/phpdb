<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Ddl\Constraint\Check;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, 'getExpressionData')]
class CheckTest extends TestCase
{
    public function testGetExpressionData()
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
