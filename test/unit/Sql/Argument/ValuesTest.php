<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Argument;

use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentType;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Values::class, '__construct')]
#[CoversMethod(Values::class, 'getType')]
#[CoversMethod(Values::class, 'getValue')]
#[CoversMethod(Values::class, 'getSpecification')]
final class ValuesTest extends TestCase
{
    /**
     * @return array<string, array{0: list<null|string|int|float|bool>, 1: string}>
     */
    public static function specificationProvider(): array
    {
        return [
            'empty set'    => [[], '(NULL)'],
            'single value' => [[1], '(%s)'],
            'three values' => [[1, 2, 3], '(%s, %s, %s)'],
        ];
    }

    /**
     * An empty set has no placeholders to render, so it degrades to a literal NULL
     * rather than an empty parenthesised list.
     *
     * @param list<null|string|int|float|bool> $values
     */
    #[Test]
    #[DataProvider('specificationProvider')]
    public function getSpecificationRendersOnePlaceholderPerValue(array $values, string $expected): void
    {
        static::assertSame($expected, (new Values($values))->getSpecification());
    }

    #[Test]
    public function getTypeReturnsValues(): void
    {
        static::assertSame(ArgumentType::Values, (new Values([1, 2]))->getType());
    }

    #[Test]
    public function getValuePreservesOrderAndContents(): void
    {
        static::assertSame([1, 'two', null, false], (new Values([1, 'two', null, false]))->getValue());
    }

    /**
     * The constructor reindexes, so a set with a gap in it still yields a list whose keys
     * line up with the positional placeholders getSpecification emits.
     */
    #[Test]
    public function getValueReindexesAGappedSet(): void
    {
        $sparse = ['a', 'b', 'c'];
        unset($sparse[1]);

        static::assertSame(['a', 'c'], (new Values($sparse))->getValue());
    }
}
