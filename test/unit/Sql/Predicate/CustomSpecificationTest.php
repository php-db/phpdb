<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Predicate\CustomSpecification;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(CustomSpecification::class)]
#[Group('unit')]
final class CustomSpecificationTest extends TestCase
{
    /** @return array<string, array{string, list<ArgumentInterface>, string}> */
    public static function expansionProvider(): array
    {
        return [
            'one value, positional placeholders'   => [
                '%1$s = ANY (ARRAY[%2$s])',
                [Argument::identifier('id'), Argument::values([1])],
                '%1$s = ANY (ARRAY[%2$s])',
            ],
            'two values, positional placeholders'  => [
                '%1$s = ANY (ARRAY[%2$s])',
                [Argument::identifier('id'), Argument::values([1, 2])],
                '%1$s = ANY (ARRAY[%2$s, %3$s])',
            ],
            'three values, plain placeholders'     => [
                '%s = ANY (ARRAY[%s])',
                [Argument::identifier('id'), Argument::values([1, 2, 3])],
                '%1$s = ANY (ARRAY[%2$s, %3$s, %4$s])',
            ],
            'list placed before the identifier'    => [
                'ARRAY[%2$s] @> ARRAY[%1$s]',
                [Argument::identifier('id'), Argument::values([1, 2])],
                'ARRAY[%2$s, %3$s] @> ARRAY[%1$s]',
            ],
            'identifier referenced twice'          => [
                '(%1$s IS NULL OR %1$s IN (%2$s))',
                [Argument::identifier('id'), Argument::values([1, 2])],
                '(%1$s IS NULL OR %1$s IN (%2$s, %3$s))',
            ],
            'escaped percent sign kept'            => [
                "%1\$s IN (%2\$s) AND 'a%%' <> ''",
                [Argument::identifier('id'), Argument::values([1, 2])],
                "%1\$s IN (%2\$s, %3\$s) AND 'a%%' <> ''",
            ],
            'escaped percent before an s kept'     => [
                "%1\$s IN (%2\$s) AND 'a%%s' <> ''",
                [Argument::identifier('id'), Argument::values([1, 2])],
                "%1\$s IN (%2\$s, %3\$s) AND 'a%%s' <> ''",
            ],
            'empty list'                           => [
                '%1$s = ANY (ARRAY[%2$s])',
                [Argument::identifier('id'), Argument::values([])],
                '%1$s = ANY (ARRAY[NULL])',
            ],
            'sub-select value set'                 => [
                '%1$s = ANY %2$s',
                [Argument::identifier('id'), Argument::select(new Select('u'))],
                '%1$s = ANY %2$s',
            ],
            'list identifier shifts the value set' => [
                'ROW(%1$s) IN (%2$s)',
                [Argument::values([1, 2]), Argument::values([3, 4])],
                'ROW(%1$s, %2$s) IN (%3$s, %4$s)',
            ],
        ];
    }

    /** @return array<string, array{string, list<ArgumentInterface>, string}> */
    public static function inconsistentSpecificationProvider(): array
    {
        $inList = [Argument::identifier('id'), Argument::values([1, 2])];

        return [
            'list referenced twice'        => [
                '%1$s IN (%2$s) OR %1$s = ANY (ARRAY[%2$s])',
                $inList,
                sprintf(
                    InvalidArgumentException::SPECIFICATION_ARGUMENT_NOT_REFERENCED_ONCE,
                    '%1$s IN (%2$s) OR %1$s = ANY (ARRAY[%2$s])',
                    2,
                    2,
                ),
            ],
            'list never referenced'        => [
                '%1$s IS NOT NULL',
                $inList,
                sprintf(InvalidArgumentException::SPECIFICATION_ARGUMENT_NOT_REFERENCED_ONCE, '%1$s IS NOT NULL', 2, 0),
            ],
            'bound value referenced twice' => [
                '%1$s IN (%2$s) OR %1$s IS NULL',
                [Argument::value('a'), Argument::values([1])],
                sprintf(
                    InvalidArgumentException::SPECIFICATION_ARGUMENT_NOT_REFERENCED_ONCE,
                    '%1$s IN (%2$s) OR %1$s IS NULL',
                    1,
                    2,
                ),
            ],
            'missing argument referenced'  => [
                '%1$s IN (%3$s)',
                $inList,
                sprintf(InvalidArgumentException::SPECIFICATION_ARGUMENT_OUT_OF_RANGE, '%1$s IN (%3$s)', 3, 2),
            ],
            'too many plain placeholders'  => [
                '%s IN (%s) OR %s',
                $inList,
                sprintf(InvalidArgumentException::SPECIFICATION_ARGUMENT_OUT_OF_RANGE, '%s IN (%s) OR %s', 3, 2),
            ],
        ];
    }

    /** @param list<ArgumentInterface> $arguments */
    #[Test]
    #[DataProvider('expansionProvider')]
    public function eachPlaceholderCoversEveryValueItsArgumentRenders(
        string $specification,
        array $arguments,
        string $expected,
    ): void {
        static::assertSame($expected, CustomSpecification::expand($specification, $arguments));
    }

    /** @param list<ArgumentInterface> $arguments */
    #[Test]
    #[DataProvider('inconsistentSpecificationProvider')]
    public function specificationThatWouldMisalignParametersIsRejected(
        string $specification,
        array $arguments,
        string $expectedMessage,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        CustomSpecification::expand($specification, $arguments);
    }

    #[Test]
    public function unboundArgumentsMayBeOmitted(): void
    {
        static::assertSame(
            'EXISTS (%2$s)',
            CustomSpecification::expand('EXISTS (%2$s)', [Argument::literal('1'), Argument::values([1])]),
        );
    }
}
