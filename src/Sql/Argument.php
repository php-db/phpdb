<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use InvalidArgumentException;

use function array_fill;
use function array_values;
use function count;
use function current;
use function implode;
use function is_array;
use function key;
use function sprintf;

/**
 * Represents a typed argument for use in SQL expressions and statements.
 *
 * Encapsulates a value along with its type designation, enabling proper handling
 * during SQL generation. Supports scalars, arrays, and SQL objects. The type is
 * automatically set to Select when the value is an Expression or SqlInterface instance.
 */
class Argument
{
    /**
     * @param null|bool|string|int|float|array|ExpressionInterface|SqlInterface $value Value to encapsulate
     * @param ArgumentType $type Type designation for the value
     * @throws InvalidArgumentException When Select type is specified without a valid SQL object value
     */
    public function __construct(
        protected null|bool|string|int|float|array|ExpressionInterface|SqlInterface $value = null,
        protected ArgumentType $type = ArgumentType::Value
    ) {
        if ($value instanceof ExpressionInterface || $value instanceof SqlInterface) {
            $type = ArgumentType::Select;
        } elseif ($type === ArgumentType::Select) {
            throw new InvalidArgumentException('Invalid argument value');
        }

        if (is_array($value)) {
            $key = key($value);
            /** @var null|bool|string|int|float|array|ArgumentType $current */
            $current = current($value);
            if ($current instanceof ArgumentType) {
                $type  = $current;
                $value = $key;
            } else {
                $value = array_values($value);
            }
        }

        $this->setType($type);
        $this->setValue($value);
    }

    /**
     * Sets the argument type.
     *
     * @param ArgumentType|string $type Type enum or legacy string constant
     * @throws InvalidArgumentException When string does not match a valid ArgumentType value
     */
    public function setType(ArgumentType|string $type): static
    {
        if (! $type instanceof ArgumentType) {
            $type = ArgumentType::tryFrom($type);
            if ($type === null) {
                throw new InvalidArgumentException('Invalid argument type');
            }
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Returns the argument type.
     */
    public function getType(): ArgumentType
    {
        return $this->type;
    }

    /**
     * Sets the argument value.
     */
    public function setValue(null|bool|string|int|float|array|ExpressionInterface|SqlInterface $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Returns the argument value.
     */
    public function getValue(): null|bool|string|int|float|array|ExpressionInterface|SqlInterface
    {
        return $this->value;
    }

    /**
     * Returns the value cast to string.
     */
    public function getValueAsString(): string
    {
        return (string) $this->value;
    }

    /**
     * Returns the specification format string for SQL generation.
     *
     * Returns a format string with placeholders appropriate for the value type.
     * Array values generate multiple placeholders within parentheses.
     */
    public function getSpecification(): string
    {
        if (is_array($this->value)) {
            return count($this->value) > 0 ?
                sprintf('(%s)', implode(', ', array_fill(0, count($this->value), '%s'))) :
                '(NULL)';
        }

        return '%s';
    }

    /**
     * Factory method for creating a Value type argument.
     */
    public static function value(null|bool|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Value);
    }

    /**
     * Factory method for creating an Identifier type argument.
     */
    public static function identifier(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Identifier);
    }

    /**
     * Factory method for creating a Literal type argument.
     */
    public static function literal(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Literal);
    }
}
