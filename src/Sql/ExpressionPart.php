<?php

namespace Laminas\Db\Sql;

class ExpressionPart
{
    /** @var string[] */
    protected array $specification = [];

    /** @var Argument[] $values */
    protected array $values = [];

    /** @param Argument[] $values */
    public function __construct(?string $specification = null, ?array $values = null)
    {
        if ($specification !== null) {
            $this->setSpecification($specification);
        }

        if ($values !== null) {
            $this->setValues($values);
        }
    }

    public function getSpecificationString(): string
    {
        return implode(' ', $this->specification);
    }

    public function getSpecification(): array
    {
        return $this->specification;
    }

    public function setSpecification(string $specification): static
    {
        $this->specification = [];
        $this->addSpecification($specification);

        return $this;
    }

    public function addSpecification(string $specification): static
    {
        $this->specification[] = $specification;

        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    /** @param Argument[] $values */
    public function setValues(array $values): static
    {
        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    public function addValue(Argument $value): static
    {
        $this->values[] = $value;

        return $this;
    }
}
