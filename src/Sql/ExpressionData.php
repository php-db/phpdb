<?php

namespace Laminas\Db\Sql;

use Countable;
use Iterator;

use function array_map;
use function array_merge;
use function count;
use function implode;

/**
 * @template TKey of array-key
 * @implements Iterator<TKey,ExpressionData>
 */
class ExpressionData implements Iterator, Countable
{
    protected int $position = 0;

    /** @var ExpressionPart[] */
    protected array $expressionParts = [];

    /** @param Argument[] $values */
    public function __construct(null|string|ExpressionPart $specificationOrPart = null, ?array $values = null)
    {
        if ($specificationOrPart !== null) {
            $this->addExpressionPart($specificationOrPart, $values);
        }
    }

    /**
     * Add part to expression
     *
     * @param Argument[] $values
     * @return $this Provides a fluent interface
     */
    public function addExpressionPart(
        null|string|ExpressionPart $specificationOrPart = null,
        ?array $values = null
    ): static {
        if ($specificationOrPart instanceof ExpressionPart) {
            $this->expressionParts[] = $specificationOrPart;
        } else {
            $this->expressionParts[] = new ExpressionPart($specificationOrPart, $values);
        }

        return $this;
    }

    /**
     * Add part to expression
     *
     * @param ExpressionPart[] $parts
     * @return $this Provides a fluent interface
     */
    public function addExpressionParts(array $parts, bool $hasBrackets = false): static
    {
        $partsCount = count($parts);

        for ($partsIndex = 0; $partsIndex < $partsCount; $partsIndex++) {
            $part = $parts[$partsIndex];
            if (! $part instanceof ExpressionPart) {
                throw new Exception\InvalidArgumentException('Expression parts must be of type ExpressionPart');
            }

            if ($hasBrackets) {
                if ($partsIndex === 0) {
                    $part->setSpecification('(' . $part->getSpecificationString());
                }
                if ($partsIndex === $partsCount - 1) {
                    $part->setSpecification($part->getSpecificationString() . ')');
                }
            }

            $this->expressionParts[] = $part;
        }

        return $this;
    }

    public function getExpressionPart(int $position): ExpressionPart
    {
        if (! isset($this->expressionParts[$position])) {
            throw new Exception\InvalidArgumentException('Expression part does not exist');
        }

        return $this->expressionParts[$position];
    }

    /**
     * @return ExpressionPart[]
     */
    public function getExpressionParts(): array
    {
        return $this->expressionParts;
    }

    public function getExpressionSpecification(): string
    {
        return implode(' ', array_map(fn (ExpressionPart $part) => $part->getSpecificationString(), $this->expressionParts));
    }

    public function getExpressionValues(): array
    {
        return array_merge(...array_map(fn (ExpressionPart $part) => $part->getValues(), $this->expressionParts));
    }

    #[Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[Override]
    public function current(): ExpressionPart
    {
        return $this->expressionParts[$this->position];
    }

    #[Override]
    public function key(): int
    {
        return $this->position;
    }

    #[Override]
    public function next(): void
    {
        ++$this->position;
    }

    #[Override]
    public function valid(): bool
    {
        return isset($this->expressionParts[$this->position]);
    }

    #[Override]
    public function count(): int
    {
        return count($this->expressionParts);
    }
}
