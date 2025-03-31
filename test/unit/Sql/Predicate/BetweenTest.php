<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Predicate\Between;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Between::class, '__construct')]
#[CoversMethod(Between::class, 'getIdentifier')]
#[CoversMethod(Between::class, 'getMinValue')]
#[CoversMethod(Between::class, 'getMaxValue')]
#[CoversMethod(Between::class, 'getSpecification')]
#[CoversMethod(Between::class, 'setIdentifier')]
#[CoversMethod(Between::class, 'setMinValue')]
#[CoversMethod(Between::class, 'setMaxValue')]
#[CoversMethod(Between::class, 'setSpecification')]
#[CoversMethod(Between::class, 'getExpressionData')]
final class BetweenTest extends TestCase
{
    protected Between $between;

    #[Override]
    protected function setUp(): void
    {
        $this->between = new Between();
    }

    public function testConstructorYieldsNullIdentifierMinimumAndMaximumValues(): void
    {
        self::assertNull($this->between->getIdentifier());
        self::assertNull($this->between->getMinValue());
        self::assertNull($this->between->getMaxValue());
    }

    public function testConstructorCanPassIdentifierMinimumAndMaximumValues(): void
    {
        $between = new Between('foo.bar', 1, 300);
        self::assertEquals('foo.bar', $between->getIdentifier());
        self::assertSame(1, $between->getMinValue());
        self::assertSame(300, $between->getMaxValue());

        $between = new Between('foo.bar', 0, 1);
        self::assertEquals('foo.bar', $between->getIdentifier());
        self::assertSame(0, $between->getMinValue());
        self::assertSame(1, $between->getMaxValue());

        $between = new Between('foo.bar', -1, 0);
        self::assertEquals('foo.bar', $between->getIdentifier());
        self::assertSame(-1, $between->getMinValue());
        self::assertSame(0, $between->getMaxValue());
    }

    public function testSpecificationHasSaneDefaultValue(): void
    {
        self::assertEquals('%1$s BETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    public function testIdentifierIsMutable(): void
    {
        $this->between->setIdentifier('foo.bar');
        self::assertEquals('foo.bar', $this->between->getIdentifier());
    }

    public function testMinValueIsMutable(): void
    {
        $this->between->setMinValue(10);
        self::assertEquals(10, $this->between->getMinValue());
    }

    public function testMaxValueIsMutable(): void
    {
        $this->between->setMaxValue(10);
        self::assertEquals(10, $this->between->getMaxValue());
    }

    public function testSpecificationIsMutable(): void
    {
        $this->between->setSpecification('%1$s IS INBETWEEN %2$s AND %3$s');
        self::assertEquals('%1$s IS INBETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->between->setIdentifier('foo.bar')
                      ->setMinValue(10)
                      ->setMaxValue(19);
        $expected = [
            [
                $this->between->getSpecification(),
                ['foo.bar', 10, 19],
                [Between::TYPE_IDENTIFIER, Between::TYPE_VALUE, Between::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $this->between->getExpressionData());

        $this->between->setIdentifier([10 => Between::TYPE_VALUE])
                      ->setMinValue(['foo.bar' => Between::TYPE_IDENTIFIER])
                      ->setMaxValue(['foo.baz' => Between::TYPE_IDENTIFIER]);
        $expected = [
            [
                $this->between->getSpecification(),
                [10, 'foo.bar', 'foo.baz'],
                [Between::TYPE_VALUE, Between::TYPE_IDENTIFIER, Between::TYPE_IDENTIFIER],
            ],
        ];
        self::assertEquals($expected, $this->between->getExpressionData());
    }
}
