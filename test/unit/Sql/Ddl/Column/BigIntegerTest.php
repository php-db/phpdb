<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\BigInteger;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\CreateTable;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(BigInteger::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
#[CoversMethod(Integer::class, 'getExpressionData')]
#[Group('unit')]
final class BigIntegerTest extends TestCase
{
    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        $createTable = new CreateTable('t');
        $createTable->addColumn(new BigInteger('i', false, null, ['length' => 20]));

        static::assertSame("CREATE TABLE \"t\" ( \n    \"i\" BIGINT(20) NOT NULL \n)", $createTable->getSqlString());
    }

    public function testGetExpressionData(): void
    {
        $column         = new BigInteger('foo');
        $expressionData = $column->getExpressionData();

        self::assertEquals(
            '%s %s NOT NULL',
            $expressionData['spec'],
        );

        self::assertEquals(
            [
                Argument::Identifier('foo'),
                Argument::Literal('BIGINT'),
            ],
            $expressionData['values'],
        );
    }

    public function testObjectConstruction(): void
    {
        $integer = new BigInteger('foo');
        self::assertEquals('foo', $integer->getName());
    }
}
