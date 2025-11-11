<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function implode;
use function is_array;

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
        return implode(' ', $this->specification);
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
}
