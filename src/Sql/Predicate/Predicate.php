<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\ExpressionInterface;

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
    private Predicate|null $unnest = null;

    protected string|null $nextPredicateCombineOperator = null;

    protected function getNextPredicateCombineOperator(): string
    {
        $operator                           = $this->nextPredicateCombineOperator ?? $this->defaultCombination;
        $this->nextPredicateCombineOperator = null;

        return $operator;
    }

    /**
     * Begin nesting predicates
     */
    public function nest(): Predicate
    {
        $predicateSet = new Predicate();
        $predicateSet->setUnnest($this);
        $this->addPredicate($predicateSet, $this->getNextPredicateCombineOperator());
        $this->nextPredicateCombineOperator = null;

        return $predicateSet;
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
     */
    public function unnest(): Predicate
    {
        if (! $this->unnest instanceof Predicate) {
            throw new RuntimeException('Not nested');
        }

        $unnest = $this->unnest;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->unnest = null;

        return $unnest;
    }

    /**
     * Create "Equal To" predicate
     * Utilizes Operator predicate
     */
    public function equalTo(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right,
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator()
        );

        return $this;
    }

    /**
     * Create "Not Equal To" predicate
     * Utilizes Operator predicate
     */
    public function notEqualTo(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_NOT_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator()
        );

        return $this;
    }

    /**
     * Create "Less Than" predicate
     * Utilizes Operator predicate
     */
    public function lessThan(
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_LESS_THAN, $right),
            $this->getNextPredicateCombineOperator()
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
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_GREATER_THAN, $right),
            $this->getNextPredicateCombineOperator()
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
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator()
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
        null|float|int|string|ArgumentInterface $left,
        null|float|int|string|ArgumentInterface $right
    ): static {
        $this->addPredicate(
            new Operator($left, Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $right),
            $this->getNextPredicateCombineOperator()
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
        null|float|int|string|ArgumentInterface $identifier,
        null|float|int|string|ArgumentInterface $like
    ): static {
        $this->addPredicate(
            new Like($identifier, $like),
            $this->getNextPredicateCombineOperator()
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
        null|float|int|string|ArgumentInterface $identifier,
        null|float|int|string|ArgumentInterface $notLike
    ): static {
        $this->addPredicate(
            new NotLike($identifier, $notLike),
            $this->getNextPredicateCombineOperator()
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
        null|string|float|int|array|ArgumentInterface|ExpressionInterface $parameters = []
    ): static {
        if ($parameters !== []) {
            $this->addPredicate(
                new Expression($expression, $parameters),
                $this->getNextPredicateCombineOperator()
            );
        } else {
            $this->addPredicate(
                new Expression($expression),
                $this->getNextPredicateCombineOperator()
            );
        }

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
            $this->getNextPredicateCombineOperator()
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
            $this->getNextPredicateCombineOperator()
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
            $this->getNextPredicateCombineOperator()
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
            $this->getNextPredicateCombineOperator()
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
            $this->getNextPredicateCombineOperator()
        );

        return $this;
    }

    /**
     * Create "between" predicate
     * Utilizes Between predicate
     *
     * @return $this Provides a fluent interface
     */
    public function between(
        null|float|int|string|array|ArgumentInterface $identifier,
        null|float|int|string|array|ArgumentInterface $minValue,
        null|float|int|string|array|ArgumentInterface $maxValue
    ): static {
        $this->addPredicate(
            new Between($identifier, $minValue, $maxValue),
            $this->getNextPredicateCombineOperator()
        );

        return $this;
    }

    /**
     * Create "NOT BETWEEN" predicate
     * Utilizes NotBetween predicate
     *
     * @return $this Provides a fluent interface
     */
    public function notBetween(
        null|float|int|string|array|ArgumentInterface $identifier,
        null|float|int|string|array|ArgumentInterface $minValue,
        null|float|int|string|array|ArgumentInterface $maxValue
    ): static {
        $this->addPredicate(
            new NotBetween($identifier, $minValue, $maxValue),
            $this->getNextPredicateCombineOperator()
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
            $this->getNextPredicateCombineOperator()
        );

        return $this;
    }

    /**
     * Overloading
     * Overloads "or", "and", "nest", and "unnest"
     */
    public function __get(string $name): Predicate
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
