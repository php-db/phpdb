<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Ddl\Constraint\UniqueKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(UniqueKey::class, 'getExpressionData')]
class UniqueKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new UniqueKey('foo', 'my_uk');
        self::assertEquals(
            [
                [
                    'CONSTRAINT %s UNIQUE (%s)',
                    ['my_uk', 'foo'],
                    [$uk::TYPE_IDENTIFIER, $uk::TYPE_IDENTIFIER],
                ],
            ],
            $uk->getExpressionData()
        );
    }
}
