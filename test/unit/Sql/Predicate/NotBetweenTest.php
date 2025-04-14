<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\ExpressionInterface;
use Laminas\Db\Sql\Predicate\NotBetween;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(NotBetween::class, 'getSpecification')]
#[CoversMethod(NotBetween::class, 'getExpressionData')]
final class NotBetweenTest extends TestCase
{
    protected NotBetween $notBetween;

    #[Override]
    protected function setUp(): void
    {
        $this->notBetween = new NotBetween();
    }

    public function testSpecificationHasSameDefaultValue(): void
    {
        self::assertEquals('%1$s NOT BETWEEN %2$s AND %3$s', $this->notBetween->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->notBetween->setIdentifier('foo.bar')
                      ->setMinValue(10)
                      ->setMaxValue(19);
        $expected = [
            [
                $this->notBetween->getSpecification(),
                ['foo.bar', 10, 19],
                [ExpressionInterface::TYPE_IDENTIFIER, ExpressionInterface::TYPE_VALUE, ExpressionInterface::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $this->notBetween->getExpressionData());

        $this->notBetween
            ->setIdentifier(10)
            ->setMinValue(['foo.bar' => ExpressionInterface::TYPE_IDENTIFIER])
            ->setMaxValue(['foo.baz' => ExpressionInterface::TYPE_IDENTIFIER]);
        $expected = [
            [
                $this->notBetween->getSpecification(),
                [10, 'foo.bar', 'foo.baz'],
                [ExpressionInterface::TYPE_VALUE, ExpressionInterface::TYPE_IDENTIFIER, ExpressionInterface::TYPE_IDENTIFIER],
            ],
        ];
        self::assertEquals($expected, $this->notBetween->getExpressionData());
    }
}
