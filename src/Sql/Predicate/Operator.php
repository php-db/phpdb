<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\SqlInterface;

final class Operator extends AbstractExpression implements PredicateInterface
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
            $this->left = $left instanceof ArgumentInterface
                ? $left
                : ($left instanceof ExpressionInterface || $left instanceof SqlInterface
                    ? new Select($left)
                    : new Identifier($left));
        }

        if ($operator !== self::OPERATOR_EQUAL_TO) {
            $this->operator = $operator;
        }

        if ($right !== null) {
            $this->right = $right instanceof ArgumentInterface
                ? $right
                : ($right instanceof ExpressionInterface || $right instanceof SqlInterface
                    ? new Select($right)
                    : new Value($right));
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
     * Get operator string
     */
    public function getOperator(): string
    {
        return $this->operator;
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
     * Get right side of operator
     */
    public function getRight(): ?ArgumentInterface
    {
        return $this->right;
    }

    /**
     * Set right side of operator
     */
    public function setRight(
        null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right
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

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->left instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if (! $this->right instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Right expression must be specified');
        }

        $leftSpec  = $this->left->getSpecification();
        $rightSpec = $this->right->getSpecification();

        return [
            'spec'   => $this->specification ?? "{$leftSpec} {$this->operator} {$rightSpec}",
            'values' => [$this->left, $this->right],
        ];
    }

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(string $q, PlatformInterface $platform): string
    {
        if (! $this->left instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if (! $this->right instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Right expression must be specified');
        }

        $leftSql = $this->left instanceof Value
            ? $platform->quoteTrustedValue($this->left->getValue())
            : ($this->left instanceof Identifier
                ? $this->left->toSql($q)
                : $this->left->getSpecification());

        $rightSql = $this->right instanceof Value
            ? $platform->quoteTrustedValue($this->right->getValue())
            : ($this->right instanceof Identifier
                ? $this->right->toSql($q)
                : $this->right->getSpecification());

        return "{$leftSql} {$this->operator} {$rightSql}";
    }
}
