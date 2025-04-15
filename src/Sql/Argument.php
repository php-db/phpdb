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
        if (is_array($value)) {
            $type  = $this->processArrayType($value);
            $value = $this->processArrayValue($value);
        }

        if ($value instanceof ExpressionInterface || $value instanceof SqlInterface) {
            $type = ArgumentType::Select;
        } elseif (is_string($value) || is_array($value) || is_float($value) || is_int($value) || $value === null) {
            throw new InvalidArgumentException('Invalid argument value');
        }

        $this->setType($type);
        $this->setValue($value);
    }

    protected function processArrayType(array $value): ArgumentType
    {
        $type = ArgumentType::Value;
        if (count($value) !== 1) {
            return $type;
        }

        $key   = key($value);
        if (is_int($key)) {
            return $type;
        }

        return ArgumentType::tryFrom($key) ?? ArgumentType::Value;
    }

    protected function processArrayValue(array $value): array|string|int|float|ExpressionInterface|SqlInterface
    {
        if (count($value) !== 1) {
            return $value;
        }

        /** @var array|string|int|float|ExpressionInterface|SqlInterface */
        return current($value);
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
}