<?php

namespace Laminas\Db\Sql;

class ExpressionPart
{
    /** @var string[] */
    protected array $specification = [];

    /** @var Argument[] $values */
    protected array $values = [];

    protected bool $isJoin = false;

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

    public function getSpecificationString(bool $decorateString = false): string
    {
        $specification = ($decorateString && $this->isJoin) ? ' %s ' : '%s';

        return sprintf($specification, implode(' ', $this->specification));
    }

    public function getSpecificationValues(array $values = []): array
    {
        foreach ($this->values as $value) {
            if (is_array($value->getValue())) {
                foreach ($value->getValue() as $v) {
                    $values[] = new Argument($v);
                }
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }

    protected function getValueArray($value, $values = []): array
    {
        foreach ($this->values as $value) {
            $values[] = $value->getValue();
        }
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

    /** @return Argument[] */
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

    public function isJoin(): bool
    {
        return $this->isJoin;
    }

    public function setIsJoin(bool $isJoin): ExpressionPart
    {
        $this->isJoin = $isJoin;

        return $this;
    }
}
