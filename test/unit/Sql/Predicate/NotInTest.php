<?php

namespace LaminasTest\Db\Sql\Predicate;

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
        $expected = [
            [
                '%s NOT IN (%s, %s, %s)',
                ['foo.bar', 1, 2, 3],
                [NotIn::TYPE_IDENTIFIER, NotIn::TYPE_VALUE, NotIn::TYPE_VALUE, NotIn::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select   = new Select();
        $in       = new NotIn('foo', $select);
        $expected = [
            [
                '%s NOT IN %s',
                ['foo', $select],
                [$in::TYPE_IDENTIFIER, $in::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select   = new Select();
        $in       = new NotIn('foo', $select);
        $expected = [
            [
                '%s NOT IN %s',
                ['foo', $select],
                [$in::TYPE_IDENTIFIER, $in::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select   = new Select();
        $in       = new NotIn(['foo', 'bar'], $select);
        $expected = [
            [
                '(%s, %s) NOT IN %s',
                ['foo', 'bar', $select],
                [$in::TYPE_IDENTIFIER, $in::TYPE_IDENTIFIER, $in::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }
}
