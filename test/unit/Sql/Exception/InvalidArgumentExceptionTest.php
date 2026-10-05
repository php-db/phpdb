<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(InvalidArgumentException::class, 'forAlreadyCombined')]
#[CoversMethod(InvalidArgumentException::class, 'forEmptyCheckExpression')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidColumnLength')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingColumnLength')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingDecimalDigits')]
#[CoversMethod(InvalidArgumentException::class, 'forEmptyExpression')]
#[CoversMethod(InvalidArgumentException::class, 'forEmptyPrefix')]
#[CoversMethod(InvalidArgumentException::class, 'forEmptySchema')]
#[CoversMethod(InvalidArgumentException::class, 'forEmptySeparator')]
#[CoversMethod(InvalidArgumentException::class, 'forEmptyTable')]
#[CoversMethod(InvalidArgumentException::class, 'forForeignTable')]
#[CoversMethod(InvalidArgumentException::class, 'describeTable')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidArgumentType')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidFromArray')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidJoinName')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidMagicProperty')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidSpecificationName')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingValuesOrSelect')]
#[CoversMethod(InvalidArgumentException::class, 'forNonNumericParameter')]
#[CoversMethod(InvalidArgumentException::class, 'forNonStringValueKey')]
#[CoversMethod(InvalidArgumentException::class, 'forReadOnlyConstructorState')]
#[CoversMethod(InvalidArgumentException::class, 'forSelectWithMergeFlag')]
#[CoversMethod(InvalidArgumentException::class, 'forUnknownArgumentType')]
#[CoversMethod(InvalidArgumentException::class, 'forUnknownColumnKey')]
#[CoversMethod(InvalidArgumentException::class, 'forValuesWithMergeFlag')]
final class InvalidArgumentExceptionTest extends TestCase
{
    /** @return array<string, array{string|TableIdentifier|array<string, string|TableIdentifier>, string}> */
    public static function foreignTableProvider(): array
    {
        return [
            'table name'                   => ['users', 'users'],
            'table identifier'             => [new TableIdentifier('users'), 'users'],
            'table identifier with schema' => [new TableIdentifier('users', 'app'), 'app.users'],
            'aliased table name'           => [['u' => 'users'], 'users AS u'],
            'aliased table identifier'     => [['u' => new TableIdentifier('users', 'app')], 'app.users AS u'],
            'empty array'                  => [[], 'null'],
        ];
    }

    /** @return array<string, array{string, list<string|int>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'missing decimal digits'      => [
                'forMissingDecimalDigits',
                ['price', 'DECIMAL'],
                InvalidArgumentException::MISSING_DECIMAL_DIGITS,
            ],
            'missing column length'       => [
                'forMissingColumnLength',
                ['name', 'VARCHAR'],
                InvalidArgumentException::MISSING_COLUMN_LENGTH,
            ],
            'invalid column length'       => [
                'forInvalidColumnLength',
                ['age'],
                InvalidArgumentException::INVALID_COLUMN_LENGTH,
            ],
            'empty check expression'      => [
                'forEmptyCheckExpression',
                [],
                InvalidArgumentException::EMPTY_CHECK_EXPRESSION,
            ],
            'already combined'            => [
                'forAlreadyCombined',
                [],
                InvalidArgumentException::ALREADY_COMBINED,
            ],
            'empty expression'            => [
                'forEmptyExpression',
                [],
                InvalidArgumentException::EMPTY_EXPRESSION,
            ],
            'empty prefix'                => [
                'forEmptyPrefix',
                [],
                InvalidArgumentException::EMPTY_PREFIX,
            ],
            'empty schema'                => [
                'forEmptySchema',
                [],
                InvalidArgumentException::EMPTY_SCHEMA,
            ],
            'empty separator'             => [
                'forEmptySeparator',
                [],
                InvalidArgumentException::EMPTY_SEPARATOR,
            ],
            'empty table'                 => [
                'forEmptyTable',
                [],
                InvalidArgumentException::EMPTY_TABLE,
            ],
            'foreign table'               => [
                'forForeignTable',
                ['users'],
                InvalidArgumentException::FOREIGN_TABLE,
            ],
            'invalid argument type'       => [
                'forInvalidArgumentType',
                [],
                InvalidArgumentException::INVALID_ARGUMENT_TYPE,
            ],
            'invalid from array'          => [
                'forInvalidFromArray',
                [],
                InvalidArgumentException::INVALID_FROM_ARRAY,
            ],
            'invalid join name'           => [
                'forInvalidJoinName',
                ['orders'],
                InvalidArgumentException::INVALID_JOIN_NAME,
            ],
            'invalid magic property'      => [
                'forInvalidMagicProperty',
                [],
                InvalidArgumentException::INVALID_MAGIC_PROPERTY,
            ],
            'invalid specification name'  => [
                'forInvalidSpecificationName',
                [],
                InvalidArgumentException::INVALID_SPECIFICATION_NAME,
            ],
            'missing values or select'    => [
                'forMissingValuesOrSelect',
                [],
                InvalidArgumentException::MISSING_VALUES_OR_SELECT,
            ],
            'non numeric parameter'       => [
                'forNonNumericParameter',
                ['PhpDb\\Sql\\Select::limit', 'string'],
                InvalidArgumentException::NON_NUMERIC_PARAMETER,
            ],
            'non string value key'        => [
                'forNonStringValueKey',
                [],
                InvalidArgumentException::NON_STRING_VALUE_KEY,
            ],
            'read only constructor state' => [
                'forReadOnlyConstructorState',
                [],
                InvalidArgumentException::READ_ONLY_CONSTRUCTOR_STATE,
            ],
            'select with merge flag'      => [
                'forSelectWithMergeFlag',
                [],
                InvalidArgumentException::SELECT_WITH_MERGE_FLAG,
            ],
            'unknown argument type'       => [
                'forUnknownArgumentType',
                [],
                InvalidArgumentException::UNKNOWN_ARGUMENT_TYPE,
            ],
            'unknown column key'          => [
                'forUnknownColumnKey',
                ['email'],
                InvalidArgumentException::UNKNOWN_COLUMN_KEY,
            ],
            'values with merge flag'      => [
                'forValuesWithMergeFlag',
                [],
                InvalidArgumentException::VALUES_WITH_MERGE_FLAG,
            ],
        ];
    }

    /**
     * @param string|TableIdentifier|array<string, string|TableIdentifier> $table
     */
    #[Test]
    #[DataProvider('foreignTableProvider')]
    public function forForeignTableDescribesEachKindOfTable(
        string|TableIdentifier|array $table,
        string $described,
    ): void {
        self::assertSame(
            sprintf(InvalidArgumentException::FOREIGN_TABLE, $described),
            InvalidArgumentException::forForeignTable($table)->getMessage(),
        );
    }

    /** @param list<string|int> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorRendersItsTemplate(string $method, array $arguments, string $template): void
    {
        $exception = InvalidArgumentException::{$method}(...$arguments);

        self::assertSame(sprintf($template, ...$arguments), $exception->getMessage());
    }

    /** @param list<string|int> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorReturnsTheComponentExceptionType(string $method, array $arguments): void
    {
        self::assertInstanceOf(ExceptionInterface::class, InvalidArgumentException::{$method}(...$arguments));
    }
}
