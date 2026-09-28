<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Argument;

use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\ArgumentType;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Identifiers::class, '__construct')]
#[CoversMethod(Identifiers::class, 'getType')]
#[CoversMethod(Identifiers::class, 'getValue')]
#[CoversMethod(Identifiers::class, 'getSpecification')]
final class IdentifiersTest extends TestCase
{
    /**
     * @return array<string, array{0: list<string>, 1: string}>
     */
    public static function specificationProvider(): array
    {
        return [
            'empty set'        => [[], '(NULL)'],
            'single column'    => [['foo'], '(%s)'],
            'multiple columns' => [['foo', 'bar', 'baz'], '(%s, %s, %s)'],
        ];
    }

    /**
     * An empty set has no placeholders to render, so it degrades to a literal NULL
     * rather than an empty parenthesised list.
     *
     * @param list<string> $identifiers
     */
    #[Test]
    #[DataProvider('specificationProvider')]
    public function getSpecificationRendersOnePlaceholderPerIdentifier(array $identifiers, string $expected): void
    {
        static::assertSame($expected, (new Identifiers($identifiers))->getSpecification());
    }

    #[Test]
    public function getTypeReturnsIdentifiers(): void
    {
        static::assertSame(ArgumentType::Identifiers, (new Identifiers(['foo']))->getType());
    }

    #[Test]
    public function getValuePreservesOrder(): void
    {
        static::assertSame(['foo', 'bar'], (new Identifiers(['foo', 'bar']))->getValue());
    }

    /**
     * The constructor reindexes, so a set with a gap in it still yields a list whose keys
     * line up with the positional placeholders getSpecification emits.
     */
    #[Test]
    public function getValueReindexesAGappedSet(): void
    {
        $sparse = ['foo', 'bar', 'baz'];
        unset($sparse[1]);

        static::assertSame(['foo', 'baz'], (new Identifiers($sparse))->getValue());
    }
}
