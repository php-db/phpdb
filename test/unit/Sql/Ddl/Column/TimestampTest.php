<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Ddl\Column\AbstractTimestampColumn;
use PhpDb\Sql\Ddl\Column\Timestamp;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Timestamp::class, 'getExpressionData')]
#[CoversMethod(AbstractTimestampColumn::class, 'getExpressionData')]
final class TimestampTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Timestamp('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData['spec']);
        self::assertEquals([
            new Identifier('foo'),
            new Literal('TIMESTAMP'),
        ], $expressionData['values']);
    }

    public function testGetExpressionDataWithOnUpdateOption(): void
    {
        $column = new Timestamp('created_at');
        $column->setOption('on_update', true);

        $expressionData = $column->getExpressionData();

        // Verify specification includes ON UPDATE
        $spec = $expressionData['spec'];
        self::assertEquals('%s %s NOT NULL %s', $spec);

        $values = $expressionData['values'];

        // Should have 3 values: identifier, type, and ON UPDATE argument
        self::assertCount(3, $values);
        self::assertEquals(new Identifier('created_at'), $values[0]);
        self::assertEquals(new Literal('TIMESTAMP'), $values[1]);

        // Third value should be the ON UPDATE argument
        self::assertInstanceOf(ArgumentInterface::class, $values[2]);
        // Verify it equals the expected Argument using factory method for consistency
        self::assertEquals(new Literal('ON UPDATE CURRENT_TIMESTAMP'), $values[2]);
    }

    public function testGetExpressionDataWithoutOnUpdateOption(): void
    {
        $column = new Timestamp('updated_at');

        $expressionData = $column->getExpressionData();

        // Should have 2 values: identifier and type (no ON UPDATE)
        $values = $expressionData['values'];
        self::assertCount(2, $values);
        self::assertEquals(new Identifier('updated_at'), $values[0]);
        self::assertEquals(Argument::literal('TIMESTAMP'), $values[1]);
    }

    public function testInheritanceFromAbstractTimestampColumn(): void
    {
        $column = new Timestamp('test');
        self::assertInstanceOf(AbstractTimestampColumn::class, $column);
    }
}
