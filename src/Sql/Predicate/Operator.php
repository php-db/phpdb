<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\SqlInterface;

class Operator extends AbstractExpression implements PredicateInterface
{
    final public const OPERATOR_EQUAL_TO = '=';

    final public const OP_EQ = '=';

    final public const OPERATOR_NOT_EQUAL_TO = '!=';

    final public const OP_NE = '!=';

    final public const OPERATOR_LESS_THAN = '<';

    final public const OP_LT = '<';

    final public const OPERATOR_LESS_THAN_OR_EQUAL_TO = '<=';

    final public const OP_LTE = '<=';

    final public const OPERATOR_GREATER_THAN = '>';

    final public const OP_GT = '>';

    final public const OPERATOR_GREATER_THAN_OR_EQUAL_TO = '>=';

    final public const OP_GTE = '>=';

    protected ?ArgumentInterface $left     = null;
    protected ?ArgumentInterface $right    = null;
    protected string             $operator = self::OPERATOR_EQUAL_TO;

    /**
     * Constructor
     */
    public function __construct(
        string|ArgumentInterface|ExpressionInterface|SqlInterface|null $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface|null $right = null,
    ) {
        if (null !== $left) {
            $this->setLeft($left);
        }

        if (self::OPERATOR_EQUAL_TO !== $operator) {
            $this->setOperator($operator);
        }

        if (null !== $right) {
            $this->setRight($right);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->left instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingLeftExpression();
        }

        if (! $this->right instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingRightExpression();
        }

        $leftSpec  = $this->left->getSpecification();
        $rightSpec = $this->right->getSpecification();

        return [
            'spec'   => $this->specification ?? "{$leftSpec} {$this->operator} {$rightSpec}",
            'values' => [$this->left, $this->right],
        ];
    }

    /**
     * Get left side of operator
     */
    public function getLeft(): ?ArgumentInterface
    {
        return $this->left;
    }

    /**
     * Get operator string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Get right side of operator
     */
    public function getRight(): ?ArgumentInterface
    {
        return $this->right;
    }

    /**
     * Set left side of operator
     */
    public function setLeft(string|ArgumentInterface|ExpressionInterface|SqlInterface $left): static
    {
        if ($left instanceof ArgumentInterface) {
            $this->left = $left;
        } elseif ($left instanceof ExpressionInterface || $left instanceof SqlInterface) {
            $this->left = new Select($left);
        } else {
            $this->left = new Identifier($left);
        }

        return $this;
    }

    /**
     * Set operator string
     */
    public function setOperator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Set right side of operator
     */
    public function setRight(
        bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface|null $right,
    ): static {
        if ($right instanceof ArgumentInterface) {
            $this->right = $right;
        } elseif ($right instanceof ExpressionInterface || $right instanceof SqlInterface) {
            $this->right = new Select($right);
        } else {
            $this->right = new Value($right);
        }

        return $this;
    }
}
