<?php

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
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

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument([1, 2, 3], ArgumentType::Value);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s NOT IN (%s, %s, %s)', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select = new Select();
        $in     = new NotIn('foo', $select);

        $identifier = new Argument('foo', ArgumentType::Identifier);
        $expression = new Argument($select, ArgumentType::Select);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s NOT IN %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select     = new Select();
        $in         = new NotIn('foo', $select);
        $identifier = new Argument('foo', ArgumentType::Identifier);
        $expression = new Argument($select, ArgumentType::Select);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s NOT IN %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select();
        $in     = new NotIn(new Argument(['foo', 'bar'], ArgumentType::Identifier), $select);

        $identifier = new Argument(['foo', 'bar'], ArgumentType::Identifier);
        $expression = new Argument($select, ArgumentType::Select);

        $expressionData = $in->getExpressionData();

        self::assertEquals('(%s, %s) NOT IN %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());
    }
}
