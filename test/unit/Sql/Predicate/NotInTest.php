<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\NotIn;
use PhpDb\Sql\Select;
use PHPUnit\Framework\TestCase;

final class NotInTest extends TestCase
{
    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new NotIn();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s NOT IN (%s, %s, %s)', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo.bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals([1, 2, 3], $values[1]->getValue());
        self::assertEquals(ArgumentType::Values, $values[1]->getType());
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select = new Select();
        $in     = new NotIn('foo', $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s NOT IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify subselect argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertSame($select, $values[1]->getValue());
        self::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select();
        $in     = new NotIn('foo', $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s NOT IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify subselect argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertSame($select, $values[1]->getValue());
        self::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select();
        $in     = new NotIn(Argument::identifiers(['foo', 'bar']), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('(%s, %s) NOT IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        self::assertCount(2, $values);

        // Verify array identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals(['foo', 'bar'], $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifiers, $values[0]->getType());

        // Verify subselect argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertSame($select, $values[1]->getValue());
        self::assertEquals(ArgumentType::Select, $values[1]->getType());
    }
}
