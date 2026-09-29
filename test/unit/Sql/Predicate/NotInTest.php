<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\NotIn;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotInTest extends TestCase
{
    #[Test]
    public function getExpressionDataWithSubselect(): void
    {
        $select = new Select();
        $in     = new NotIn('foo', $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s NOT IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[Test]
    public function getExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select();
        $in     = new NotIn(Argument::identifiers(['foo', 'bar']), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('(%s, %s) NOT IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify array identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertEquals(['foo', 'bar'], $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifiers, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[Test]
    public function getExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select();
        $in     = new NotIn('foo', $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s NOT IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new NotIn();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s NOT IN (%s, %s, %s)', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo.bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertEquals([1, 2, 3], $values[1]->getValue());
        static::assertEquals(ArgumentType::Values, $values[1]->getType());
    }
}
