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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Timestamp::class, 'getExpressionData')]
#[CoversMethod(AbstractTimestampColumn::class, 'getExpressionData')]
final class TimestampTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Timestamp('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Literal('TIMESTAMP'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithCurrentTimestampDefault(): void
    {
        $column = new Timestamp('created_at');
        $column->setDefault(new Literal('CURRENT_TIMESTAMP'));

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('created_at'),
                new Literal('TIMESTAMP'),
                new Literal('CURRENT_TIMESTAMP'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithCurrentTimestampDefaultAndOnUpdate(): void
    {
        $column = new Timestamp('updated_at');
        $column->setDefault(new Literal('CURRENT_TIMESTAMP'));
        $column->setOption('on_update', true);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL DEFAULT %s %s', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('updated_at'),
                new Literal('TIMESTAMP'),
                new Literal('CURRENT_TIMESTAMP'),
                new Literal('ON UPDATE CURRENT_TIMESTAMP'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithOnUpdateOption(): void
    {
        $column = new Timestamp('created_at');
        $column->setOption('on_update', true);

        $expressionData = $column->getExpressionData();

        // Verify specification includes ON UPDATE
        $spec = $expressionData['spec'];
        static::assertSame('%s %s NOT NULL %s', $spec);

        $values = $expressionData['values'];

        // Should have 3 values: identifier, type, and ON UPDATE argument
        static::assertCount(3, $values);
        static::assertEquals(new Identifier('created_at'), $values[0]);
        static::assertEquals(new Literal('TIMESTAMP'), $values[1]);

        // Third value should be the ON UPDATE argument
        static::assertInstanceOf(ArgumentInterface::class, $values[2]);
        // Verify it equals the expected Argument using factory method for consistency
        static::assertEquals(new Literal('ON UPDATE CURRENT_TIMESTAMP'), $values[2]);
    }

    #[Test]
    public function getExpressionDataWithoutOnUpdateOption(): void
    {
        $column = new Timestamp('updated_at');

        $expressionData = $column->getExpressionData();

        // Should have 2 values: identifier and type (no ON UPDATE)
        $values = $expressionData['values'];
        static::assertCount(2, $values);
        static::assertEquals(new Identifier('updated_at'), $values[0]);
        static::assertEquals(Argument::literal('TIMESTAMP'), $values[1]);
    }

    #[Test]
    public function inheritanceFromAbstractTimestampColumn(): void
    {
        $column = new Timestamp('test');
        static::assertInstanceOf(AbstractTimestampColumn::class, $column);
    }
}
