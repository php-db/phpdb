<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(PrimaryKey::class, 'getExpressionData')]
final class PrimaryKeyTest extends TestCase
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
