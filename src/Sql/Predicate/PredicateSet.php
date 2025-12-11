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
use function is_array;
use function is_scalar;
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

    /** SQL clause prefix (e.g., 'WHERE', 'HAVING') - override in subclasses */
    protected string $prefix = '';

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
        $this->predicates[] = [$combination ?? $this->defaultCombination, $predicate];

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
        // Fast path: array is the most common case
        if (is_array($predicates)) {
            foreach ($predicates as $pkey => $pvalue) {
                // Fast path: string key with scalar value (most common)
                if (is_string($pkey)) {
                    // Most common case: simple equality check with scalar
                    if (is_scalar($pvalue)) {
                        $this->predicates[] = [$combination, new Operator($pkey, Operator::OP_EQ, $pvalue)];
                    } elseif ($pvalue === null) {
                        $this->predicates[] = [$combination, new IsNull($pkey)];
                    } elseif (is_array($pvalue)) {
                        $this->predicates[] = [$combination, new In($pkey, $pvalue)];
                    } elseif (str_contains($pkey, '?')) {
                        $this->predicates[] = [$combination, new PredicateExpression($pkey, $pvalue)];
                    } elseif ($pvalue instanceof PredicateInterface) {
                        throw new Exception\InvalidArgumentException(
                            'Using Predicate must not use string keys'
                        );
                    } else {
                        $this->predicates[] = [$combination, new Operator($pkey, Operator::OP_EQ, $pvalue)];
                    }
                } elseif ($pvalue instanceof PredicateInterface) {
                    $this->predicates[] = [$combination, $pvalue];
                } elseif ($pvalue instanceof Expression) {
                    $this->predicates[] = [$combination, new PredicateExpression(
                        $pvalue->getExpression(),
                        $pvalue->getParameters()
                    )];
                } else {
                    $predicate = str_contains($pvalue, Expression::PLACEHOLDER)
                        ? new PredicateExpression($pvalue) : new Literal($pvalue);
                    $this->predicates[] = [$combination, $predicate];
                }
            }

            return $this;
        }

        if ($predicates instanceof PredicateInterface) {
            $this->predicates[] = [$combination, $predicates];

            return $this;
        }

        if ($predicates instanceof Closure) {
            $predicates($this);

            return $this;
        }

        // String predicate
        $predicate = str_contains($predicates, Expression::PLACEHOLDER)
            ? new PredicateExpression($predicates) : new Literal($predicates);
        $this->predicates[] = [$combination, $predicate];

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

        $spec      = '';
        $allValues = [];
        $first     = true;

        foreach ($this->predicates as [$operator, $predicate]) {
            $expressionData = $predicate->getExpressionData();

            $partSpec = $predicate instanceof self
                ? "({$expressionData['spec']})"
                : $expressionData['spec'];

            $spec .= $first ? $partSpec : " {$operator} {$partSpec}";
            $first = false;

            $values = $expressionData['values'];
            if ($values !== []) {
                foreach ($values as $value) {
                    $allValues[] = $value;
                }
            }
        }

        return [
            'spec'   => $spec,
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

    /** @inheritDoc */
    #[Override]
    public function toSqlPart(array &$values): string
    {
        $predicateCount = count($this->predicates);

        if ($predicateCount === 0) {
            return '';
        }

        if ($predicateCount === 1) {
            [$operator, $predicate] = $this->predicates[0];
            $sql                    = $predicate->toSqlPart($values);

            if ($predicate instanceof self) {
                $sql = "({$sql})";
            }

            return $this->prefix !== '' ? ' ' . $this->prefix . ' ' . $sql : $sql;
        }

        $result = '';
        $first  = true;

        foreach ($this->predicates as [$operator, $predicate]) {
            $sql = $predicate->toSqlPart($values);

            // Wrap nested predicate sets in parentheses
            if ($predicate instanceof self && $predicate->count() > 1) {
                $sql = '(' . $sql . ')';
            }

            $result .= $first ? $sql : " {$operator} {$sql}";
            $first   = false;
        }

        return $this->prefix !== '' ? ' ' . $this->prefix . ' ' . $result : $result;
    }
}
