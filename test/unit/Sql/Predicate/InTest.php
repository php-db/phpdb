<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Select as ArgumentSelect;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(In::class, '__construct')]
#[CoversMethod(In::class, 'setIdentifier')]
#[CoversMethod(In::class, 'getIdentifier')]
#[CoversMethod(In::class, 'setValueSet')]
#[CoversMethod(In::class, 'getValueSet')]
#[CoversMethod(In::class, 'getExpressionData')]
#[Group('unit')]
final class InTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function canPassIdentifierAndEmptyValueSetToConstructor(): void
    {
        $in = new In('foo.bar', []);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify empty value set was set correctly
        $valueSet = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet);
        static::assertEquals([], $valueSet->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canPassIdentifierAndValueSetToConstructor(): void
    {
        $in = new In('foo.bar', [1, 2]);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify value set was set correctly
        $valueSet = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet);
        static::assertEquals([1, 2], $valueSet->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function emptyConstructorYieldsNullIdentifierAndValueSet(): void
    {
        $in = new In();
        static::assertNull($in->getIdentifier());
        static::assertNull($in->getValueSet());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $in = new In();
        $in->setValueSet([1, 2]);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_IDENTIFIER);
        $in->getExpressionData();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataThrowsExceptionWhenValueSetNotSet(): void
    {
        $in = new In();
        $in->setIdentifier('foo');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_VALUE_SET);
        $in->getExpressionData();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataWithEmptyValues(): void
    {
        new Select();
        $in = new In('foo', []);

        $expressionData = $in->getExpressionData();

        static::assertSame('%s IN (NULL)', $expressionData['spec']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataWithSubselect(): void
    {
        $select = new Select();
        $in     = new In(Argument::value('foo'), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify value argument (passed as value type)
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select();
        $in     = new In(Argument::identifiers(['foo', 'bar']), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('(%s, %s) IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify array identifiers argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertEquals(['foo', 'bar'], $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifiers, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select();
        $in     = new In(Argument::identifier('foo'), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN %s', $expressionData['spec']);

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function identifierIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setIdentifier('foo.bar');

        // Verify fluent interface
        static::assertSame($in, $result);

        // Verify the first mutation occurred
        $identifier1 = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier1);
        static::assertSame('foo.bar', $identifier1->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation with different data to verify mutability
        $in->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier2);
        static::assertSame('baz.qux', $identifier2->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new In();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN (%s, %s, %s)', $expressionData['spec']);

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

        // Test with typed value sets
        $in->setIdentifier('foo.bar')
            ->setValueSet([
                [1 => ArgumentType::Literal],
                [2 => ArgumentType::Value],
                [3 => ArgumentType::Literal],
            ]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN (%s, %s, %s)', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo.bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument with types
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertEquals(
            [
                [1 => ArgumentType::Literal],
                [2 => ArgumentType::Value],
                [3 => ArgumentType::Literal],
            ],
            $values[1]->getValue(),
        );
        static::assertEquals(ArgumentType::Values, $values[1]->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setValueSetWithArgumentInterfacePassesThrough(): void
    {
        $in     = new In();
        $values = new Values([1, 2, 3]);

        $in->setValueSet($values);

        static::assertSame($values, $in->getValueSet());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setValueSetWithSelectWrapsInArgumentSelect(): void
    {
        $in     = new In();
        $select = new Select('users');

        $in->setValueSet($select);

        $valueSet = $in->getValueSet();
        static::assertInstanceOf(ArgumentSelect::class, $valueSet);
        static::assertSame(ArgumentType::Select, $valueSet->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function valueSetIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setValueSet([1, 2]);

        // Verify fluent interface
        static::assertSame($in, $result);

        // Verify the first mutation occurred
        $valueSet1 = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet1);
        static::assertEquals([1, 2], $valueSet1->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet1->getType());

        // Second mutation with different data to verify mutability
        $in->setValueSet([3, 4, 5]);

        // Verify the instance was actually mutated
        $valueSet2 = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet2);
        static::assertEquals([3, 4, 5], $valueSet2->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet2->getType());
    }
}
