<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Countable;
use Iterator;
use Override;

use function array_map;
use function array_merge;
use function count;
use function implode;

/**
 * Container for managing multiple expression parts that form a complete SQL expression.
 *
 * Aggregates ExpressionPart instances into a cohesive expression, providing methods
 * to assemble specification strings and collect argument values. Implements Iterator
 * and Countable for traversing the contained parts.
 *
 * @template TKey of array-key
 * @implements Iterator<TKey,ExpressionData>
 */
class ExpressionData implements Iterator, Countable
{
    protected int $position = 0;

    /** @var ExpressionPart[] */
    protected array $expressionParts = [];

    /**
     * @param string|ExpressionPart|null $specificationOrPart Initial specification or part
     * @param Argument[]|null $values Initial values when providing specification string
     */
    public function __construct(null|string|ExpressionPart $specificationOrPart = null, ?array $values = null)
    {
        if ($specificationOrPart !== null) {
            $this->addExpressionPart($specificationOrPart, $values);
        }
    }

    /**
     * Adds a single expression part.
     *
     * @param string|ExpressionPart|null $specificationOrPart Specification string or ExpressionPart instance
     * @param Argument[]|null $values Values when providing specification string
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
     * Adds multiple expression parts at once.
     *
     * @param ExpressionPart[] $parts Array of expression parts
     * @param bool $hasBrackets Whether to wrap parts in parentheses
     * @throws Exception\InvalidArgumentException When array contains non-ExpressionPart items.
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

    /**
     * Returns a single expression part by position.
     *
     * @throws Exception\InvalidArgumentException When position does not exist.
     */
    public function getExpressionPart(int $position): ExpressionPart
    {
        if (! isset($this->expressionParts[$position])) {
            throw new Exception\InvalidArgumentException('Expression part does not exist');
        }

        return $this->expressionParts[$position];
    }

    /**
     * Returns all expression parts.
     *
     * @return ExpressionPart[]
     */
    public function getExpressionParts(): array
    {
        return $this->expressionParts;
    }

    /**
     * Assembles all parts into a single specification string.
     */
    public function getExpressionSpecification(): string
    {
        return implode(
            ' ',
            array_map(fn (ExpressionPart $part) => $part->getSpecificationString(), $this->expressionParts)
        );
    }

    /**
     * Collects all argument values from all parts.
     *
     * @return Argument[]
     */
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
