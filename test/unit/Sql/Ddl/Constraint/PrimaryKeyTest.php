<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(PrimaryKey::class, 'getExpressionData')]
final class PrimaryKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $pk = new PrimaryKey('foo');
        self::assertEquals(
            [
                [
                    'PRIMARY KEY (%s)',
                    ['foo'],
                    [$pk::TYPE_IDENTIFIER],
                ],
            ],
            $pk->getExpressionData()
        );
    }
}
