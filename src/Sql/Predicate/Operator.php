<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\Select;

class Operator extends AbstractExpression implements PredicateInterface
{
    public const OPERATOR_EQUAL_TO                 = '=';
    public const OP_EQ                             = '=';
    public const OPERATOR_NOT_EQUAL_TO             = '!=';
    public const OP_NE                             = '!=';
    public const OPERATOR_LESS_THAN                = '<';
    public const OP_LT                             = '<';
    public const OPERATOR_LESS_THAN_OR_EQUAL_TO    = '<=';
    public const OP_LTE                            = '<=';
    public const OPERATOR_GREATER_THAN             = '>';
    public const OP_GT                             = '>';
    public const OPERATOR_GREATER_THAN_OR_EQUAL_TO = '>=';
    public const OP_GTE                            = '>=';

    protected ?Argument $left  = null;
    protected ?Argument $right = null;
    protected string $operator = self::OPERATOR_EQUAL_TO;

    /**
     * Constructor
     */
    public function __construct(
        null|bool|string|int|float|array|Argument|Expression|Select $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        null|bool|string|int|float|array|Argument|Expression|Select $right = null
    ) {
        if ($left !== null) {
            $this->setLeft($left);
        }

        if ($operator !== self::OPERATOR_EQUAL_TO) {
            $this->setOperator($operator);
        }

        if ($right !== null) {
            $this->setRight($right);
        }
    }

    /**
     * Get left side of operator
     */
    public function getLeft(): ?Argument
    {
        return $this->left;
    }

    /**
     * Set left side of operator
     *
     * @return $this Provides a fluent interface
     */
    public function setLeft(
        null|bool|string|int|float|array|Expression|Select|Argument $left,
        ArgumentType $type = ArgumentType::Identifier
    ): static {
        $this->left = $left instanceof Argument ? $left : new Argument($left, $type);

        return $this;
    }

    /**
     * Get operator string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Set operator string
     *
     * @return $this Provides a fluent interface
     */
    public function setOperator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get right side of operator
     */
    public function getRight(): ?Argument
    {
        return $this->right;
    }

    /**
     * Set right side of operator
     *
     * @return $this Provides a fluent interface
     */
    public function setRight(
        null|bool|string|int|float|array|Expression|Select|Argument $right,
        ArgumentType $type = ArgumentType::Value
    ): static {
        $this->right = $right instanceof Argument ? $right : new Argument($right, $type);

        return $this;
    }

    /**
     * Get predicate parts for where statement
     */
    #[Override]
    public function getExpressionData(): ExpressionData
    {
        if (! $this->left instanceof Argument) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if (! $this->right instanceof Argument) {
            throw new InvalidArgumentException('Right expression must be specified');
        }

        return new ExpressionData(
            '%s ' . $this->operator . ' %s',
            [
                $this->left,
                $this->right,
            ]
        );
    }
}
