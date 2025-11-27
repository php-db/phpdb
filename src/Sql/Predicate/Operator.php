<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\SqlInterface;

class Operator extends AbstractExpression implements PredicateInterface
{
    public const OPERATOR_EQUAL_TO = '=';

    public const OP_EQ = '=';

    public const OPERATOR_NOT_EQUAL_TO = '!=';

    public const OP_NE = '!=';

    public const OPERATOR_LESS_THAN = '<';

    public const OP_LT = '<';

    public const OPERATOR_LESS_THAN_OR_EQUAL_TO = '<=';

    public const OP_LTE = '<=';

    public const OPERATOR_GREATER_THAN = '>';

    public const OP_GT = '>';

    public const OPERATOR_GREATER_THAN_OR_EQUAL_TO = '>=';

    public const OP_GTE = '>=';

    protected ?ArgumentInterface $left  = null;
    protected ?ArgumentInterface $right = null;
    protected string $operator          = self::OPERATOR_EQUAL_TO;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface|ExpressionInterface|SqlInterface $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right = null
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
    public function getLeft(): ?ArgumentInterface
    {
        return $this->left;
    }

    /**
     * Set left side of operator
     *
     * @return $this Provides a fluent interface
     */
    public function setLeft(string|ArgumentInterface|ExpressionInterface|SqlInterface $left): static
    {
        if ($left instanceof ArgumentInterface) {
            $this->left = $left;
        } elseif ($left instanceof ExpressionInterface || $left instanceof SqlInterface) {
            $this->left = Argument::select($left);
        } else {
            $this->left = Argument::identifier($left);
        }

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
    public function getRight(): ?ArgumentInterface
    {
        return $this->right;
    }

    /**
     * Set right side of operator
     *
     * @return $this Provides a fluent interface
     */
    public function setRight(null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right): static
    {
        if ($right instanceof ArgumentInterface) {
            $this->right = $right;
        } elseif ($right instanceof ExpressionInterface || $right instanceof SqlInterface) {
            $this->right = Argument::select($right);
        } else {
            $this->right = Argument::value($right);
        }

        return $this;
    }

    /**
     * Get predicate parts for where statement
     */
    #[Override]
    public function getExpressionData(): ExpressionData
    {
        if (! $this->left instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if (! $this->right instanceof ArgumentInterface) {
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
