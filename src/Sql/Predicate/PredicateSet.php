<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Closure;
use Countable;
use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Expression as PredicateExpression;
use ReturnTypeWillChange;

use function count;
use function is_array;
use function is_scalar;
use function is_string;
use function str_contains;

class PredicateSet extends AbstractExpression implements PredicateInterface, Countable
{
    final public const OP_AND = 'AND';

    final public const OP_OR = 'OR';

    /** @deprecated Use OP_AND instead */
    final public const COMBINED_BY_AND = self::OP_AND;

    /** @deprecated Use OP_OR instead */
    final public const COMBINED_BY_OR = self::OP_OR;

    /**
     * @deprecated Use ArgumentInterface (Identifier, Value) instead
     */
    final public const TYPE_IDENTIFIER = 'identifier';

    /**
     * @deprecated Use ArgumentInterface (Identifier, Value) instead
     */
    final public const TYPE_VALUE = 'value';

    /**
     * @deprecated Use ArgumentInterface (Identifier, Value) instead
     */
    final public const TYPE_LITERAL = 'literal';

    protected string $defaultCombination = self::OP_AND;

    /** @var PredicateInterface[] */
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
        $predicate->setCombination($combination ?? $this->defaultCombination);
        $this->predicates[] = $predicate;

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
        if (is_array($predicates)) {
            foreach ($predicates as $pkey => $pvalue) {
                if (is_string($pkey)) {
                    if (is_scalar($pvalue)) {
                        $predicate = new Operator($pkey, Operator::OP_EQ, $pvalue);
                    } elseif ($pvalue === null) {
                        $predicate = new IsNull($pkey);
                    } elseif (is_array($pvalue)) {
                        $predicate = new In($pkey, $pvalue);
                    } elseif (str_contains($pkey, '?')) {
                        $predicate = new PredicateExpression($pkey, $pvalue);
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
                        ? new PredicateExpression($pvalue) : new Literal($pvalue);
                }

                $predicate->setCombination($combination);
                $this->predicates[] = $predicate;
            }

            return $this;
        }

        if ($predicates instanceof PredicateInterface) {
            $predicates->setCombination($combination);
            $this->predicates[] = $predicates;

            return $this;
        }

        if ($predicates instanceof Closure) {
            $predicates($this);

            return $this;
        }

        $predicate = str_contains($predicates, Expression::PLACEHOLDER)
            ? new PredicateExpression($predicates) : new Literal($predicates);
        $predicate->setCombination($combination);
        $this->predicates[] = $predicate;

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
        return $this->addPredicate($predicate, self::OP_OR);
    }

    /**
     * Add predicate using AND operator
     */
    public function andPredicate(PredicateInterface $predicate): static
    {
        return $this->addPredicate($predicate, self::OP_AND);
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if ($this->predicates === []) {
            return ['spec' => '', 'values' => []];
        }

        $spec      = '';
        $allValues = [];
        $first     = true;

        foreach ($this->predicates as $predicate) {
            $expressionData = $predicate->getExpressionData();

            $partSpec = $predicate instanceof self
                ? "({$expressionData['spec']})"
                : $expressionData['spec'];

            $spec .= $first ? $partSpec : ' ' . $predicate->getCombination() . ' ' . $partSpec;
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
    public function toSqlPart(string $q, PlatformInterface $platform): string
    {
        if ($this->predicates === []) {
            return '';
        }

        $result = '';
        $first  = true;

        foreach ($this->predicates as $predicate) {
            $sql = $predicate->toSqlPart($q, $platform);

            if ($predicate instanceof self && $predicate->count() > 1) {
                $sql = '(' . $sql . ')';
            }

            $result .= $first ? $sql : ' ' . $predicate->getCombination() . ' ' . $sql;
            $first   = false;
        }

        return $this->prefix !== '' ? ' ' . $this->prefix . ' ' . $result : $result;
    }
}
