<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Argument\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(In::class, '__construct')]
#[CoversMethod(In::class, 'setIdentifier')]
#[CoversMethod(In::class, 'getIdentifier')]
#[CoversMethod(In::class, 'setValueSet')]
#[CoversMethod(In::class, 'getValueSet')]
#[CoversMethod(In::class, 'getExpressionData')]
final class InTest extends TestCase
{
    public function testEmptyConstructorYieldsNullIdentifierAndValueSet(): void
    {
        $in = new In();
        self::assertNull($in->getIdentifier());
        self::assertNull($in->getValueSet());
    }

    public function testCanPassIdentifierAndValueSetToConstructor(): void
    {
        $in = new In('foo.bar', [1, 2]);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify value set was set correctly
        $valueSet = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet);
        self::assertEquals([1, 2], $valueSet->getValue());
        self::assertEquals(ArgumentType::Value, $valueSet->getType());
    }

    public function testCanPassIdentifierAndEmptyValueSetToConstructor(): void
    {
        $in = new In('foo.bar', []);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify empty value set was set correctly
        $valueSet = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet);
        self::assertEquals([], $valueSet->getValue());
        self::assertEquals(ArgumentType::Value, $valueSet->getType());
    }

    public function testIdentifierIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setIdentifier('foo.bar');

        // Verify fluent interface
        self::assertSame($in, $result);

        // Verify the first mutation occurred
        $identifier1 = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier1);
        self::assertEquals('foo.bar', $identifier1->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation with different data to verify mutability
        $in->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier2);
        self::assertEquals('baz.qux', $identifier2->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    public function testValueSetIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setValueSet([1, 2]);

        // Verify fluent interface
        self::assertSame($in, $result);

        // Verify the first mutation occurred
        $valueSet1 = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet1);
        self::assertEquals([1, 2], $valueSet1->getValue());
        self::assertEquals(ArgumentType::Value, $valueSet1->getType());

        // Second mutation with different data to verify mutability
        $in->setValueSet([3, 4, 5]);

        // Verify the instance was actually mutated
        $valueSet2 = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet2);
        self::assertEquals([3, 4, 5], $valueSet2->getValue());
        self::assertEquals(ArgumentType::Value, $valueSet2->getType());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new In();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s IN (%s, %s, %s)', $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo.bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals([1, 2, 3], $values[1]->getValue());
        self::assertEquals(ArgumentType::Value, $values[1]->getType());

        // Test with typed value sets
        $in->setIdentifier('foo.bar')
            ->setValueSet([
                [1 => ArgumentType::Literal],
                [2 => ArgumentType::Value],
                [3 => ArgumentType::Literal],
            ]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s IN (%s, %s, %s)', $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo.bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument with types
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals([
            [1 => ArgumentType::Literal],
            [2 => ArgumentType::Value],
            [3 => ArgumentType::Literal],
        ], $values[1]->getValue());
        self::assertEquals(ArgumentType::Value, $values[1]->getType());
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select = new Select();
        $in     = new In(Argument::value('foo'), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s IN %s', $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(2, $values);

        // Verify value argument (passed as value type)
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo', $values[0]->getValue());
        self::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify subselect argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertSame($select, $values[1]->getValue());
        self::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    public function testGetExpressionDataWithEmptyValues(): void
    {
        new Select();
        $in = new In('foo', []);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s IN (NULL)', $expressionData->getExpressionSpecification());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select();
        $in     = new In(Argument::identifier('foo'), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('%s IN %s', $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
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
        $in     = new In(Argument::identifiers(['foo', 'bar']), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        self::assertEquals('(%s, %s) IN %s', $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(2, $values);

        // Verify array identifiers argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals(['foo', 'bar'], $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifiers, $values[0]->getType());

        // Verify subselect argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertSame($select, $values[1]->getValue());
        self::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    public function testGetExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $in = new In();
        $in->setValueSet([1, 2]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be specified');
        $in->getExpressionData();
    }

    public function testGetExpressionDataThrowsExceptionWhenValueSetNotSet(): void
    {
        $in = new In();
        $in->setIdentifier('foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value set must be provided for IN predicate');
        $in->getExpressionData();
    }
}
