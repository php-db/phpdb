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

class Argument
{
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

    public function getType(): ArgumentType
    {
        return $this->type;
    }

    public function setValue(null|bool|string|int|float|array|ExpressionInterface|SqlInterface $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): null|bool|string|int|float|array|ExpressionInterface|SqlInterface
    {
        return $this->value;
    }

    public function getValueAsString(): string
    {
        return (string) $this->value;
    }

    public function getSpecification(): string
    {
        if (is_array($this->value)) {
            return count($this->value) > 0 ?
                sprintf('(%s)', implode(', ', array_fill(0, count($this->value), '%s'))) :
                '(NULL)';
        }

        return '%s';
    }

    public static function value(null|bool|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Value);
    }

    public static function identifier(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Identifier);
    }

    public static function literal(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Literal);
    }
}
