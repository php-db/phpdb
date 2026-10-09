<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\Exception\RuntimeException;

/**
 * @property Predicate $and
 * @property Predicate $or
 * @property Predicate $AND
 * @property Predicate $OR
 * @property Predicate $nest
 * @property Predicate $unnest
 * @property Predicate $NEST
 * @property Predicate $UNNEST
 */
class Predicate extends PredicateSet
{
    private ?Predicate $unnest = null;

    protected ?string $nextPredicateCombineOperator = null;

    /**
     * Create "between" predicate
     * Utilizes Between predicate
     *
     * @return $this Provides a fluent interface
     */
    public function between(
        float|int|string|array|ArgumentInterface|null $identifier,
        float|int|string|array|ArgumentInterface|null $minValue,
        float|int|string|array|ArgumentInterface|null $maxValue,
    ): static {
        $this->addPredicate(
            new Between($identifier, $minValue, $maxValue),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Equal To" predicate
     * Utilizes Operator predicate
     */
    public function equalTo(
        float|int|string|ArgumentInterface|null $left,
        float|int|string|ArgumentInterface|null $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create an expression, with parameter placeholders
     *
     * @return $this Provides a fluent interface
     */
    public function expression(
        string $expression,
        string|float|int|array|ArgumentInterface|ExpressionInterface|null $parameters = [],
    ): static {
        $this->addPredicate(
            [] === $parameters ? new Expression($expression) : new Expression($expression, $parameters),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Greater Than" predicate
     * Utilizes Operator predicate
     *
     * @return $this Provides a fluent interface
     */
    public function greaterThan(
        float|int|string|ArgumentInterface|null $left,
        float|int|string|ArgumentInterface|null $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_GREATER_THAN, $right),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Greater Than Or Equal To" predicate
     * Utilizes Operator predicate
     *
     * @return $this Provides a fluent interface
     */
    public function greaterThanOrEqualTo(
        float|int|string|ArgumentInterface|null $left,
        float|int|string|ArgumentInterface|null $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "IN" predicate
     * Utilizes In predicate
     *
     * @return $this Provides a fluent interface
     */
    public function in(float|int|string|ArgumentInterface $identifier, array|ArgumentInterface $valueSet): static
    {
        $this->addPredicate(
            new In($identifier, $valueSet),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "IS NOT NULL" predicate
     * Utilizes IsNotNull predicate
     *
     * @return $this Provides a fluent interface
     */
    public function isNotNull(float|int|string|ArgumentInterface $identifier): static
    {
        $this->addPredicate(
            new IsNotNull($identifier),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "IS NULL" predicate
     * Utilizes IsNull predicate
     *
     * @return $this Provides a fluent interface
     */
    public function isNull(float|int|string|ArgumentInterface $identifier): static
    {
        $this->addPredicate(
            new IsNull($identifier),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Less Than" predicate
     * Utilizes Operator predicate
     */
    public function lessThan(
        float|int|string|ArgumentInterface|null $left,
        float|int|string|ArgumentInterface|null $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_LESS_THAN, $right),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Less Than Or Equal To" predicate
     * Utilizes Operator predicate
     *
     * @return $this Provides a fluent interface
     */
    public function lessThanOrEqualTo(
        float|int|string|ArgumentInterface|null $left,
        float|int|string|ArgumentInterface|null $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Like" predicate
     * Utilizes Like predicate
     *
     * @return $this Provides a fluent interface
     */
    public function like(
        float|int|string|ArgumentInterface|null $identifier,
        float|int|string|ArgumentInterface|null $like,
    ): static {
        $this->addPredicate(
            new Like($identifier, $like),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Literal" predicate
     * Literal predicate, for parameters, use expression()
     *
     * @return $this Provides a fluent interface
     */
    public function literal(string $literal): static
    {
        $this->addPredicate(
            new Literal($literal),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Begin nesting predicates
     */
    public function nest(): self
    {
        $predicateSet = new Predicate();
        $predicateSet->setUnnest($this);
        $this->addPredicate($predicateSet, $this->getNextPredicateCombineOperator());
        $this->nextPredicateCombineOperator = null;

        return $predicateSet;
    }

    /**
     * Create "NOT BETWEEN" predicate
     * Utilizes NotBetween predicate
     *
     * @return $this Provides a fluent interface
     */
    public function notBetween(
        float|int|string|array|ArgumentInterface|null $identifier,
        float|int|string|array|ArgumentInterface|null $minValue,
        float|int|string|array|ArgumentInterface|null $maxValue,
    ): static {
        $this->addPredicate(
            new NotBetween($identifier, $minValue, $maxValue),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "Not Equal To" predicate
     * Utilizes Operator predicate
     */
    public function notEqualTo(
        float|int|string|ArgumentInterface|null $left,
        float|int|string|ArgumentInterface|null $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_NOT_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "NOT IN" predicate
     * Utilizes NotIn predicate
     *
     * @return $this Provides a fluent interface
     */
    public function notIn(float|int|string|ArgumentInterface $identifier, array|ArgumentInterface $valueSet): static
    {
        $this->addPredicate(
            new NotIn($identifier, $valueSet),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Create "notLike" predicate
     * Utilizes In predicate
     *
     * @return $this Provides a fluent interface
     */
    public function notLike(
        float|int|string|ArgumentInterface|null $identifier,
        float|int|string|ArgumentInterface|null $notLike,
    ): static {
        $this->addPredicate(
            new NotLike($identifier, $notLike),
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Use given predicate directly
     * Contrary to {@link addPredicate()} this method respects formerly set
     * AND / OR combination operator, thus allowing generic predicates to be
     * used fluently within where chains as any other concrete predicate.
     *
     * @return $this Provides a fluent interface
     */
    // phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle
    public function predicate(PredicateInterface $predicate): static
    {
        $this->addPredicate(
            $predicate,
            $this->getNextPredicateCombineOperator(),
        );

        return $this;
    }

    /**
     * Indicate what predicate will be unnested
     */
    public function setUnnest(?Predicate $predicate = null): void
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->unnest = $predicate;
    }

    /**
     * Indicate end of nested predicate
     *
     * @throws RuntimeException If this predicate is not nested.
     */
    public function unnest(): self
    {
        if (! $this->unnest instanceof Predicate) {
            throw RuntimeException::forNotNested();
        }

        $unnest = $this->unnest;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->unnest = null;

        return $unnest;
    }

    protected function getNextPredicateCombineOperator(): string
    {
        $operator                           = $this->nextPredicateCombineOperator ?? $this->defaultCombination;
        $this->nextPredicateCombineOperator = null;

        return $operator;
    }

    /**
     * Overloading
     * Overloads "or", "and", "nest", and "unnest"
     *
     * @throws RuntimeException If "unnest" is accessed on a predicate that is not nested.
     */
    public function __get(string $name): self
    {
        switch ($name) {
            case 'or':
                $this->nextPredicateCombineOperator = self::OP_OR;
                break;
            case 'and':
                $this->nextPredicateCombineOperator = self::OP_AND;
                break;
            case 'nest':
                return $this->nest();
            case 'unnest':
                return $this->unnest();
        }

        return $this;
    }
}
