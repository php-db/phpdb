<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate\Exception;

use PhpDb\Sql\Exception;

class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string INVALID_COMBINATION = "Invalid combination: expected 'AND' or 'OR'";

    final public const string MISSING_IDENTIFIER = 'Identifier must be specified';

    final public const string MISSING_LEFT_EXPRESSION = 'Left expression must be specified';

    final public const string MISSING_LIKE_EXPRESSION = 'Like expression must be specified';

    final public const string MISSING_MAX_VALUE = 'maxValue must be specified';

    final public const string MISSING_MIN_VALUE = 'minValue must be specified';

    final public const string MISSING_RIGHT_EXPRESSION = 'Right expression must be specified';

    final public const string MISSING_VALUE_SET = 'Value set must be provided for IN predicate';

    final public const string PREDICATE_WITH_STRING_KEY = 'Using Predicate must not use string keys';

    public static function forInvalidCombination(): self
    {
        return new self(self::INVALID_COMBINATION);
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

    public static function forPredicateWithStringKey(): self
    {
        return new self(self::PREDICATE_WITH_STRING_KEY);
    }
}
