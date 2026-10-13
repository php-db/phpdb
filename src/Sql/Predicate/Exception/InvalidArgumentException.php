<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate\Exception;

use PhpDb\Sql\Exception;

use function sprintf;

/**
 * @api
 */
class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string INVALID_COMBINATION = "Invalid combination: expected 'AND' or 'OR'";

    final public const string INVALID_LIST_PREDICATE = 'A predicate without a string key must be a predicate, an expression or a string';

    final public const string MIXED_CASE_MAGIC_PROPERTY = 'Magic property "%s" must be given in lower or upper case';

    final public const string MISSING_IDENTIFIER = 'Identifier must be specified';

    final public const string MISSING_LEFT_EXPRESSION = 'Left expression must be specified';

    final public const string MISSING_LIKE_EXPRESSION = 'Like expression must be specified';

    final public const string MISSING_MAX_VALUE = 'maxValue must be specified';

    final public const string MISSING_MIN_VALUE = 'minValue must be specified';

    final public const string MISSING_RIGHT_EXPRESSION = 'Right expression must be specified';

    final public const string MISSING_VALUE_SET = 'Value set must be provided for IN predicate';

    final public const string PREDICATE_WITH_STRING_KEY = 'Using Predicate must not use string keys';

    final public const string SPECIFICATION_ARGUMENT_NOT_REFERENCED_ONCE = 'Specification "%s" must reference argument %d exactly once because it binds values, but references it %d times';

    final public const string SPECIFICATION_ARGUMENT_OUT_OF_RANGE = 'Specification "%s" references argument %d, but only %d arguments are available';

    public static function forInvalidCombination(): self
    {
        return new self(self::INVALID_COMBINATION);
    }

    public static function forInvalidListPredicate(): self
    {
        return new self(self::INVALID_LIST_PREDICATE);
    }

    public static function forMissingIdentifier(): self
    {
        return new self(self::MISSING_IDENTIFIER);
    }

    public static function forMissingLeftExpression(): self
    {
        return new self(self::MISSING_LEFT_EXPRESSION);
    }

    public static function forMissingLikeExpression(): self
    {
        return new self(self::MISSING_LIKE_EXPRESSION);
    }

    public static function forMissingMaxValue(): self
    {
        return new self(self::MISSING_MAX_VALUE);
    }

    public static function forMissingMinValue(): self
    {
        return new self(self::MISSING_MIN_VALUE);
    }

    public static function forMissingRightExpression(): self
    {
        return new self(self::MISSING_RIGHT_EXPRESSION);
    }

    public static function forMissingValueSet(): self
    {
        return new self(self::MISSING_VALUE_SET);
    }

    public static function forMixedCaseMagicProperty(string $name): self
    {
        return new self(sprintf(self::MIXED_CASE_MAGIC_PROPERTY, $name));
    }

    public static function forPredicateWithStringKey(): self
    {
        return new self(self::PREDICATE_WITH_STRING_KEY);
    }

    public static function forSpecificationArgumentNotReferencedOnce(
        string $specification,
        int $position,
        int $references,
    ): self {
        return new self(sprintf(
            self::SPECIFICATION_ARGUMENT_NOT_REFERENCED_ONCE,
            $specification,
            $position,
            $references,
        ));
    }

    public static function forSpecificationArgumentOutOfRange(
        string $specification,
        int $position,
        int $available,
    ): self {
        return new self(sprintf(self::SPECIFICATION_ARGUMENT_OUT_OF_RANGE, $specification, $position, $available));
    }
}
