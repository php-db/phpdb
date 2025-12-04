<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Closure;
use Countable;
use Override;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Expression as PredicateExpression;
use ReturnTypeWillChange;

use function count;
use function implode;
use function is_array;
use function is_string;
use function str_contains;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class PredicateSet implements PredicateInterface, Countable
{
    final public const OP_AND = 'AND';

    final public const OP_OR = 'OR';

    /** @deprecated Use OP_AND instead */
    final public const COMBINED_BY_AND = self::OP_AND;

    /** @deprecated Use OP_OR instead */
    final public const COMBINED_BY_OR = self::OP_OR;

    protected string $defaultCombination = self::OP_AND;

    protected array $predicates = [];

    /**
     * Constructor
     */
    public function __construct(?array $predicates = null, string $defaultCombination = self::OP_AND)
    {
        $this->defaultCombination = $defaultCombination;

        if ($predicates !== null) {
            foreach ($predicates as $predicate) {
                $this->addPredicate($predicate);
            }
        }
    }

    /**
     * Add predicate to set
     */
    public function addPredicate(PredicateInterface $predicate, ?string $combination = null): static
    {
        $combination ??= $this->defaultCombination;

        match ($combination) {
            self::OP_AND => $this->andPredicate($predicate),
            self::OP_OR => $this->orPredicate($predicate),
            default => throw new Exception\InvalidArgumentException(
                "Invalid combination: expected 'AND' or 'OR'"
            ),
        };

        return $this;
    }

    /**
     * Add predicates to set
     *
     * @throws Exception\InvalidArgumentException
     */
    public function addPredicates(
        PredicateInterface|Closure|string|array $predicates,
        string $combination = self::OP_AND
    ): static {
        if ($predicates instanceof PredicateInterface) {
            $this->addPredicate($predicates, $combination);

            return $this;
        }

        if ($predicates instanceof Closure) {
            $predicates($this);

            return $this;
        }

        if (is_string($predicates)) {
            $predicate = str_contains($predicates, Expression::PLACEHOLDER)
                ? new PredicateExpression($predicates) : new Literal($predicates);
            $this->addPredicate($predicate, $combination);

            return $this;
        }

        foreach ($predicates as $pkey => $pvalue) {
            if (is_string($pkey)) {
                if (str_contains($pkey, '?')) {
                    $predicate = new PredicateExpression($pkey, $pvalue);
                } elseif ($pvalue === null) {
                    $predicate = new IsNull($pkey);
                } elseif (is_array($pvalue)) {
                    $predicate = new In($pkey, $pvalue);
                } elseif ($pvalue instanceof PredicateInterface) {
                    throw new Exception\InvalidArgumentException(
                        'Using Predicate must not use string keys'
                    );
                } else {
                    $predicate = new Operator($pkey, Operator::OP_EQ, $pvalue);
                }
            } elseif ($pvalue instanceof PredicateInterface) {
                $predicate = $pvalue;
            } elseif ($pvalue instanceof Expression) {
                $predicate = new PredicateExpression(
                    $pvalue->getExpression(),
                    $pvalue->getParameters()
                );
            } else {
                $predicate = str_contains($pvalue, Expression::PLACEHOLDER)
                    ? new Expression($pvalue) : new Literal($pvalue);
            }

            $this->addPredicate($predicate, $combination);
        }

        return $this;
    }

    /**
     * Return the predicates
     */
    public function getPredicates(): array
    {
        return $this->predicates;
    }

    /**
     * Add predicate using OR operator
     */
    public function orPredicate(PredicateInterface $predicate): static
    {
        $this->predicates[] = [self::OP_OR, $predicate];

        return $this;
    }

    /**
     * Add predicate using AND operator
     */
    public function andPredicate(PredicateInterface $predicate): static
    {
        $this->predicates[] = [self::OP_AND, $predicate];

        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $predicateCount = count($this->predicates);

        if ($predicateCount === 0) {
            return ['spec' => '', 'values' => []];
        }

        if ($predicateCount === 1) {
            [$operator, $predicate] = $this->predicates[0];
            $expressionData         = $predicate->getExpressionData();

            if ($predicate instanceof self) {
                return [
                    'spec'   => "({$expressionData['spec']})",
                    'values' => $expressionData['values'],
                ];
            }

            return $expressionData;
        }

        $specParts = [];
        $allValues = [];
        $first     = true;

        foreach ($this->predicates as [$operator, $predicate]) {
            $expressionData = $predicate->getExpressionData();

            $spec = $predicate instanceof self
                ? "({$expressionData['spec']})"
                : $expressionData['spec'];

            $specParts[] = $first ? $spec : "{$operator} {$spec}";
            $first       = false;

            $values = $expressionData['values'];
            if ($values !== []) {
                foreach ($values as $value) {
                    $allValues[] = $value;
                }
            }
        }

        return [
            'spec'   => implode(' ', $specParts),
            'values' => $allValues,
        ];
    }

    /**
     * Get count of attached predicates
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->predicates);
    }
}
