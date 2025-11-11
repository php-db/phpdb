<?php

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\NotBetween;
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
                [
                    ArgumentType::Identifier,
                    ArgumentType::Value,
                    ArgumentType::Value,
                ],
            ],
        ];
        self::assertEquals($expected, $this->notBetween->getExpressionData());

        $this->notBetween
            ->setIdentifier([10 => ArgumentType::Value])
            ->setMinValue(['foo.bar' => ArgumentType::Identifier])
            ->setMaxValue(['foo.baz' => ArgumentType::Identifier]);
        $expected = [
            [
                $this->notBetween->getSpecification(),
                [10, 'foo.bar', 'foo.baz'],
                [
                    ArgumentType::Value,
                    ArgumentType::Identifier,
                    ArgumentType::Identifier,
                ],
            ],
        ];
        self::assertEquals($expected, $this->notBetween->getExpressionData());
    }
}
