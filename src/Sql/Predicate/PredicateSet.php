<?php

namespace PhpDb\Sql\Predicate;

use Closure;
use Countable;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionPart;
use PhpDb\Sql\Predicate\Expression as PredicateExpression;
use ReturnTypeWillChange;

use function count;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_contains;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class PredicateSet implements PredicateInterface, Countable
{
    public const COMBINED_BY_AND = 'AND';
    public const OP_AND          = 'AND';
    public const COMBINED_BY_OR  = 'OR';
    public const OP_OR           = 'OR';

    protected string $defaultCombination = self::COMBINED_BY_AND;
    protected array $predicates          = [];

    /**
     * Constructor
     *
     * @param null|array $predicates
     * @param string     $defaultCombination
     */
    public function __construct(?array $predicates = null, string $defaultCombination = self::COMBINED_BY_AND)
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
     *
     * @return $this Provides a fluent interface
     */
    public function addPredicate(PredicateInterface $predicate, ?string $combination = null): static
    {
        if ($combination === null || ! in_array($combination, [self::OP_AND, self::OP_OR])) {
            $combination = $this->defaultCombination;
        }

        if ($combination === self::OP_OR) {
            $this->orPredicate($predicate);

            return $this;
        }

        $this->andPredicate($predicate);

        return $this;
    }

    /**
     * Add predicates to set
     *
     * @throws Exception\InvalidArgumentException
     * @return $this Provides a fluent interface
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
            // String $predicate should be passed as an expression
            $predicate = str_contains($predicates, Expression::PLACEHOLDER)
                ? new PredicateExpression($predicates) : new Literal($predicates);
            $this->addPredicate($predicate, $combination);

            return $this;
        }

        if (is_array($predicates)) {
            foreach ($predicates as $pkey => $pvalue) {
                // loop through predicates
                if (is_string($pkey)) {
                    if (str_contains($pkey, '?')) {
                        // First, process strings that the abstraction replacement character ?
                        // as an Expression predicate
                        $predicate = new PredicateExpression($pkey, $pvalue);
                    } elseif ($pvalue === null) {
                        // Otherwise, if still a string, do something intelligent with the PHP type provided
                        // map PHP null to SQL IS NULL expression
                        $predicate = new IsNull($pkey);
                    } elseif (is_array($pvalue)) {
                        // if the value is an array, assume IN() is desired
                        $predicate = new In($pkey, $pvalue);
                    } elseif ($pvalue instanceof PredicateInterface) {
                        throw new Exception\InvalidArgumentException(
                            'Using Predicate must not use string keys'
                        );
                    } else {
                        // otherwise assume that array('foo' => 'bar') means "foo" = 'bar'
                        $predicate = new Operator($pkey, Operator::OP_EQ, $pvalue);
                    }
                } elseif ($pvalue instanceof PredicateInterface) {
                    // Predicate type is ok
                    $predicate = $pvalue;
                } else {
                    $predicate = str_contains($pvalue, Expression::PLACEHOLDER)
                        ? new Expression($pvalue) : new Literal($pvalue);
                }

                $this->addPredicate($predicate, $combination);
            }
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
     *
     * @return $this Provides a fluent interface
     */
    public function orPredicate(PredicateInterface $predicate): static
    {
        $this->predicates[] = [self::OP_OR, $predicate];

        return $this;
    }

    /**
     * Add predicate using AND operator
     *
     * @return $this Provides a fluent interface
     */
    public function andPredicate(PredicateInterface $predicate): static
    {
        $this->predicates[] = [self::OP_AND, $predicate];

        return $this;
    }

    /**
     * Get predicate parts for where statement
     */
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = new ExpressionData();

        for ($i = 0, $count = count($this->predicates); $i < $count; $i++) {
            /** @var PredicateInterface $predicate */
            $predicate = $this->predicates[$i][1];

            $expressionData->addExpressionParts(
                $predicate->getExpressionData()->getExpressionParts(),
                $predicate instanceof PredicateSet
            );

            if (isset($this->predicates[$i + 1])) {
                $expressionPart = new ExpressionPart(sprintf('%s', $this->predicates[$i + 1][0]));
                $expressionData->addExpressionPart($expressionPart);
            }
        }

        return $expressionData;
    }

    /**
     * Get count of attached predicates
     */
    #[\Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->predicates);
    }
}
