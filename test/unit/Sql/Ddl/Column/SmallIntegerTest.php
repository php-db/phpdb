<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PhpDb\Sql\Ddl\CreateTable;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(SmallInteger::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
#[CoversMethod(Integer::class, 'getExpressionData')]
#[Group('unit')]
final class SmallIntegerTest extends TestCase
{
    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        $createTable = new CreateTable('t');
        $createTable->addColumn(new SmallInteger('i', false, null, ['length' => 6]));

        static::assertSame("CREATE TABLE \"t\" ( \n    \"i\" SMALLINT(6) NOT NULL \n)", $createTable->getSqlString());
    }

    public function testGetExpressionData(): void
    {
        $column         = new SmallInteger('foo');
        $expressionData = $column->getExpressionData();

        self::assertEquals(
            '%s %s NOT NULL',
            $expressionData['spec'],
        );

        self::assertEquals(
            [
                Argument::Identifier('foo'),
                Argument::Literal('SMALLINT'),
            ],
            $expressionData['values'],
        );
    }

    public function testObjectConstruction(): void
    {
        $integer = new SmallInteger('foo');
        self::assertEquals('foo', $integer->getName());
    }
}
