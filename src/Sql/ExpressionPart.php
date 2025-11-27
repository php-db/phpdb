<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function implode;
use function is_array;

/**
 * Represents a single part of an SQL expression with its specification and values.
 *
 * Encapsulates a specification string fragment and associated argument values
 * that together form a portion of an SQL expression. Multiple parts can be
 * combined through ExpressionData to construct complete SQL expressions.
 */
class ExpressionPart
{
    /** @var string[] */
    protected array $specification = [];

    /** @var ArgumentInterface[] $values */
    protected array $values = [];

    protected bool $isJoin = false;

    /**
     * @param string|null $specification Format string for this expression part
     * @param ArgumentInterface[]|null $values Argument values for this part
     */
    public function __construct(?string $specification = null, ?array $values = null)
    {
        if ($specification !== null) {
            $this->setSpecification($specification);
        }

        if ($values !== null) {
            $this->setValues($values);
        }
    }

    /**
     * Returns the specification as a joined string.
     *
     * @param bool $decorateString Reserved for future use
     */
    public function getSpecificationString(bool $decorateString = false): string
    {
        return implode(' ', $this->specification);
    }

    /**
     * Returns flattened argument values, expanding arrays into individual arguments.
     *
     * @param ArgumentInterface[] $values Accumulator for collecting values
     * @return ArgumentInterface[]
     */
    public function getSpecificationValues(array $values = []): array
    {
        foreach ($this->values as $value) {
            if (is_array($value->getValue())) {
                foreach ($value->getValue() as $v) {
                    $values[] = Argument::value($v);
                }
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Replaces the specification string, clearing any existing specification.
     */
    public function setSpecification(string $specification): static
    {
        $this->specification = [];
        $this->addSpecification($specification);

        return $this;
    }

    /**
     * Appends to the specification string.
     */
    public function addSpecification(string $specification): static
    {
        $this->specification[] = $specification;

        return $this;
    }

    /**
     * Returns the argument values for this part.
     *
     * @return ArgumentInterface[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Replaces all argument values with the provided array.
     *
     * @param ArgumentInterface[] $values
     */
    public function setValues(array $values): static
    {
        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    /**
     * Adds a single argument value.
     */
    public function addValue(ArgumentInterface $value): static
    {
        $this->values[] = $value;

        return $this;
    }
}
