<?php

declare(strict_types=1);

namespace Laminas\Db\Sql;

use InvalidArgumentException;

use function is_array;

class Argument
{
    public function __construct(
        protected null|string|int|float|array|ExpressionInterface|SqlInterface $value = null,
        protected ArgumentType $type = ArgumentType::Value
    ) {
        if ($value instanceof ExpressionInterface || $value instanceof SqlInterface) {
            $type = ArgumentType::Select;
        } elseif ($type === ArgumentType::Select) {
            throw new InvalidArgumentException('Invalid argument value');
        }

        if (is_array($value)) {
            $key     = key($value);
            /** @var null|string|int|float|array|ArgumentType $current */
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
        if (! ($type instanceof ArgumentType)) {
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

    public function setValue(null|string|int|float|array|ExpressionInterface|SqlInterface $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): null|string|int|float|array|ExpressionInterface|SqlInterface
    {
        return $this->value;
    }

    public function getValueAsString(): string
    {
        return (string) $this->value;
    }

    public function getSpecification(): string
    {
        return (is_array($this->value)) ?
            sprintf('(%s)', implode(', ', array_fill(0, count($this->value), '%s'))) :
            '%s';
    }

    static public function value(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Value);
    }

    static public function identifier(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Identifier);
    }

    static public function literal(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Literal);
    }

    static public function select(null|string|int|float|array|ExpressionInterface|SqlInterface $value): Argument
    {
        return new self($value, ArgumentType::Value);
    }
}