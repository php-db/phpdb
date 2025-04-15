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
        } elseif (is_array($value)) {
            $key     = key($value);
            $current = current($value);
            if ($current instanceof ArgumentType) {
                $type  = $current;
                $value = $key;
            } else {
                $type    = ArgumentType::Value;
                $value = array_values($value);
            }
        } elseif ($type === ArgumentType::Select) {
            throw new InvalidArgumentException('Invalid argument value');
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
}