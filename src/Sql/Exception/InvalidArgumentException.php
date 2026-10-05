<?php

declare(strict_types=1);

namespace PhpDb\Sql\Exception;

use PhpDb\Exception;
use PhpDb\Sql\TableIdentifier;

use function array_key_first;
use function get_debug_type;
use function is_string;
use function sprintf;

class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string ALREADY_COMBINED =
        'This Select object is already combined'
            . ' and cannot be combined with multiple Selects objects';

    final public const string EMPTY_CHECK_EXPRESSION =
        'Check constraint expression must not be'
            . ' an empty string.';

    final public const string EMPTY_EXPRESSION = 'Supplied expression must not be an empty string.';

    final public const string EMPTY_PREFIX = '$prefix must be a valid table prefix or null, empty string given';

    final public const string EMPTY_SCHEMA = '$schema must be a valid schema name or null, empty string given';

    final public const string EMPTY_SEPARATOR = '$separator must be a valid table separator, empty string given';

    final public const string EMPTY_TABLE = '$table must be a valid table name, empty string given';

    final public const string FOREIGN_TABLE =
        'This Sql object is intended to work with only the table "%s"'
            . ' provided at construction time.';

    final public const string INVALID_ARGUMENT_TYPE = 'Invalid Argument type';

    final public const string INVALID_COLUMN_LENGTH =
        'Column "%s" length option must be'
            . ' a non-negative integer';

    final public const string INVALID_FROM_ARRAY =
        'from() expects $table as an array'
            . ' is a single element associative array';

    final public const string INVALID_JOIN_NAME = "join() expects '%s' as a single element associative array";

    final public const string INVALID_MAGIC_PROPERTY = 'Not a valid magic property for this object';

    final public const string INVALID_SPECIFICATION_NAME = 'Not a valid specification name.';

    final public const string MISSING_COLUMN_LENGTH = 'Column "%s" of type %s requires a length';

    final public const string MISSING_DECIMAL_DIGITS =
        'Column "%s" of type %s has a decimal scale'
            . ' but no digits';

    final public const string MISSING_VALUES_OR_SELECT = 'values or select should be present';

    final public const string NON_NUMERIC_PARAMETER = '%s expects parameter to be numeric, "%s" given';

    final public const string NON_STRING_VALUE_KEY = 'set() expects a string for the value key';

    final public const string READ_ONLY_CONSTRUCTOR_STATE =
        'Since this object was created with a table'
            . ' and/or schema in the constructor, it is read only.';

    final public const string SELECT_WITH_MERGE_FLAG =
        'A PhpDb\Sql\Select instance cannot be provided'
            . ' with the merge flag';

    final public const string UNKNOWN_ARGUMENT_TYPE = 'Unknown argument type';

    final public const string UNKNOWN_COLUMN_KEY = 'The key %s was not found in this objects column list';

    final public const string VALUES_WITH_MERGE_FLAG =
        'An array of values cannot be provided with the merge flag'
            . ' when a PhpDb\Sql\Select instance already exists as the value source';

    public static function forAlreadyCombined(): self
    {
        return new self(self::ALREADY_COMBINED);
    }

    public static function forEmptyCheckExpression(): self
    {
        return new self(self::EMPTY_CHECK_EXPRESSION);
    }

    public static function forEmptyExpression(): self
    {
        return new self(self::EMPTY_EXPRESSION);
    }

    public static function forEmptyPrefix(): self
    {
        return new self(self::EMPTY_PREFIX);
    }

    public static function forEmptySchema(): self
    {
        return new self(self::EMPTY_SCHEMA);
    }

    public static function forEmptySeparator(): self
    {
        return new self(self::EMPTY_SEPARATOR);
    }

    public static function forEmptyTable(): self
    {
        return new self(self::EMPTY_TABLE);
    }

    /**
     * @param string|TableIdentifier|array<string, string|TableIdentifier> $table
     */
    public static function forForeignTable(string|TableIdentifier|array $table): self
    {
        return new self(sprintf(self::FOREIGN_TABLE, self::describeTable($table)));
    }

    public static function forInvalidArgumentType(): self
    {
        return new self(self::INVALID_ARGUMENT_TYPE);
    }

    public static function forInvalidColumnLength(string $name): self
    {
        return new self(sprintf(self::INVALID_COLUMN_LENGTH, $name));
    }

    public static function forInvalidFromArray(): self
    {
        return new self(self::INVALID_FROM_ARRAY);
    }

    public static function forInvalidJoinName(string $name): self
    {
        return new self(sprintf(self::INVALID_JOIN_NAME, $name));
    }

    public static function forInvalidMagicProperty(): self
    {
        return new self(self::INVALID_MAGIC_PROPERTY);
    }

    public static function forInvalidSpecificationName(): self
    {
        return new self(self::INVALID_SPECIFICATION_NAME);
    }

    public static function forMissingColumnLength(string $name, string $type): self
    {
        return new self(sprintf(self::MISSING_COLUMN_LENGTH, $name, $type));
    }

    public static function forMissingDecimalDigits(string $name, string $type): self
    {
        return new self(sprintf(self::MISSING_DECIMAL_DIGITS, $name, $type));
    }

    public static function forMissingValuesOrSelect(): self
    {
        return new self(self::MISSING_VALUES_OR_SELECT);
    }

    public static function forNonNumericParameter(string $method, string $givenType): self
    {
        return new self(sprintf(self::NON_NUMERIC_PARAMETER, $method, $givenType));
    }

    public static function forNonStringValueKey(): self
    {
        return new self(self::NON_STRING_VALUE_KEY);
    }

    public static function forReadOnlyConstructorState(): self
    {
        return new self(self::READ_ONLY_CONSTRUCTOR_STATE);
    }

    public static function forSelectWithMergeFlag(): self
    {
        return new self(self::SELECT_WITH_MERGE_FLAG);
    }

    public static function forUnknownArgumentType(): self
    {
        return new self(self::UNKNOWN_ARGUMENT_TYPE);
    }

    public static function forUnknownColumnKey(string $name): self
    {
        return new self(sprintf(self::UNKNOWN_COLUMN_KEY, $name));
    }

    public static function forValuesWithMergeFlag(): self
    {
        return new self(self::VALUES_WITH_MERGE_FLAG);
    }

    /**
     * @param string|TableIdentifier|array<string, string|TableIdentifier> $table
     */
    private static function describeTable(string|TableIdentifier|array $table): string
    {
        if (is_string($table)) {
            return $table;
        }

        if ($table instanceof TableIdentifier) {
            [$name, $schema] = $table->getTableAndSchema();

            return null === $schema ? $name : "{$schema}.{$name}";
        }

        $alias  = (string) array_key_first($table);
        $target = $table[$alias] ?? null;

        return null === $target
            ? get_debug_type($target)
            : self::describeTable($target) . " AS {$alias}";
    }
}
