<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Predicate\NotIn;
use Laminas\Db\Sql\Select;
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

        $expected = [
            [
                '%s NOT IN (%s, %s, %s)',
                [$identifier, $expression],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select   = new Select();
        $in       = new NotIn('foo', $select);

        $identifier = new Argument('foo', ArgumentType::Identifier);
        $expression = new Argument($select, ArgumentType::Select);

        $expected = [
            [
                '%s NOT IN %s',
                [$identifier, $expression]
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select     = new Select();
        $in         = new NotIn('foo', $select);
        $identifier = new Argument('foo', ArgumentType::Identifier);
        $expression = new Argument($select, ArgumentType::Select);
        $expected = [
            [
                '%s NOT IN %s',
                [$identifier, $expression]
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select   = new Select();
        $in       = new NotIn(new Argument(['foo', 'bar'], ArgumentType::Identifier), $select);

        $identifier = new Argument(['foo', 'bar'], ArgumentType::Identifier);
        $expression = new Argument($select, ArgumentType::Select);

        $expected = [
            [
                '(%s, %s) NOT IN %s',
                [$identifier, $expression],
            ],
        ];

        self::assertEquals($expected, $in->getExpressionData());
    }
}
