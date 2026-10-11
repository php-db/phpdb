<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Closure;
use Countable;
use Override;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Expression as PredicateExpression;
use PhpDb\Sql\SqlInterface;
use ReturnTypeWillChange;

use function array_push;
use function count;
use function implode;
use function is_array;
use function is_string;
use function str_contains;

/**
 * @psalm-import-type ExpressionParameter from Expression
 * @psalm-type PredicateValue = ExpressionParameter|PredicateInterface|SqlInterface
 */
class PredicateSet implements PredicateInterface, Countable
{
    final public const string OP_AND = 'AND';

    final public const string OP_OR = 'OR';

    /** @deprecated Use OP_AND instead */
    final public const string COMBINED_BY_AND = self::OP_AND;

    /** @deprecated Use OP_OR instead */
    final public const string COMBINED_BY_OR = self::OP_OR;

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

        foreach ($predicates ?? [] as $predicate) {
            $this->addPredicate($predicate);
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
     * @param PredicateInterface|Closure|string|array<array-key, PredicateValue> $predicates
     * @throws Exception\InvalidArgumentException
     */
    public function addPredicates(
        PredicateInterface|Closure|string|array $predicates,
        string $combination = self::OP_AND,
    ): static {
        if ($predicates instanceof Closure) {
            $predicates($this);

            return $this;
        }

        foreach (is_array($predicates) ? $predicates : [$predicates] as $key => $value) {
            $this->addPredicate(
                is_string($key) ? $this->createColumnPredicate($key, $value) : $this->createListPredicate($value),
                $combination,
            );
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
        $specParts = [];
        $allValues = [];

        foreach ($this->predicates as [$operator, $predicate]) {
            $expressionData = $predicate->getExpressionData();

            $spec = $predicate instanceof self
                ? "({$expressionData['spec']})"
                : $expressionData['spec'];

            $specParts[] = [] === $specParts ? $spec : "{$operator} {$spec}";

            array_push($allValues, ...$expressionData['values']);
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

    /**
     * Build the predicate for an entry keyed by a column name or an expression with placeholders
     *
     * @param PredicateValue $value
     * @throws Exception\InvalidArgumentException
     */
    private function createColumnPredicate(string $key, mixed $value): PredicateInterface
    {
        return match (true) {
            str_contains($key, Expression::PLACEHOLDER) => new PredicateExpression(
                $key,
                $value instanceof SqlInterface ? new Select($value) : $value,
            ),
            null === $value => new IsNull($key),
            is_array($value) => new In($key, $value),
            $value instanceof PredicateInterface
                => throw Exception\InvalidArgumentException::forPredicateWithStringKey(),
            default => new Operator($key, Operator::OP_EQ, $value),
        };
    }

    /**
     * Build the predicate for an entry without a string key
     *
     * @param PredicateValue $value
     * @throws Exception\InvalidArgumentException
     */
    private function createListPredicate(mixed $value): PredicateInterface
    {
        return match (true) {
            $value instanceof PredicateInterface => $value,
            $value instanceof Expression => new PredicateExpression($value->getExpression(), $value->getParameters()),
            ! is_string($value) => throw Exception\InvalidArgumentException::forInvalidListPredicate(),
            str_contains($value, Expression::PLACEHOLDER) => new PredicateExpression($value),
            default                                       => new Literal($value),
        };
    }
}
