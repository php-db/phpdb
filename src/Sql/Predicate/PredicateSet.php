<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Closure;
use Countable;
use Override;
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

    /** @var list<array{0: string, 1: PredicateInterface}> */
    protected array $predicates = [];

    /**
     * Constructor
     *
     * @param list<PredicateInterface>|null $predicates
     */
    public function __construct(?array $predicates = null, string $defaultCombination = self::OP_AND)
    {
        $this->defaultCombination = $defaultCombination;

        if (null !== $predicates) {
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
            default => throw Exception\InvalidArgumentException::forInvalidCombination(),
        };

        return $this;
    }

    /**
     * Add predicates to set
     *
     * @param PredicateInterface|Closure|string|array<array-key, mixed> $predicates
     * @throws Exception\InvalidArgumentException
     */
    public function addPredicates(
        PredicateInterface|Closure|string|array $predicates,
        string $combination = self::OP_AND,
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
                ? new PredicateExpression($predicates)
                : new Literal($predicates);
            $this->addPredicate($predicate, $combination);

            return $this;
        }

        foreach ($predicates as $pkey => $pvalue) {
            $predicate = match (true) {
                is_string($pkey) => match (true) {
                    str_contains($pkey, '?') => new PredicateExpression($pkey, $pvalue),
                    null === $pvalue => new IsNull($pkey),
                    is_array($pvalue) => new In($pkey, $pvalue),
                    $pvalue instanceof PredicateInterface
                        => throw Exception\InvalidArgumentException::forPredicateWithStringKey(),
                    default => new Operator($pkey, Operator::OP_EQ, $pvalue),
                },
                $pvalue instanceof PredicateInterface => $pvalue,
                $pvalue instanceof Expression => new PredicateExpression(
                    $pvalue->getExpression(),
                    $pvalue->getParameters(),
                ),
                str_contains($pvalue, Expression::PLACEHOLDER) => new Expression($pvalue),
                default                                        => new Literal($pvalue),
            };

            $this->addPredicate($predicate, $combination);
        }

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

    /**
     * Get count of attached predicates
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->predicates);
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $predicateCount = count($this->predicates);

        if (0 === $predicateCount) {
            return ['spec' => '', 'values' => []];
        }

        if (1 === $predicateCount) {
            [$operator, $predicate] = $this->predicates[0];
            $expressionData = $predicate->getExpressionData();

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
            if ([] !== $values) {
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
     * Return the predicates
     *
     * @return list<array{0: string, 1: PredicateInterface}>
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
}
