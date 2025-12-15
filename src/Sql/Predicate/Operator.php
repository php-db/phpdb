<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\SqlInterface;

use function is_scalar;
use function is_string;
use function str_contains;
use function str_replace;

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

    /** @var null|string|ArgumentInterface|ExpressionInterface|SqlInterface */
    protected null|string|ArgumentInterface|ExpressionInterface|SqlInterface $left = null;

    /** @var null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface */
    protected null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right = null;

    protected string $operator = self::OPERATOR_EQUAL_TO;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface|ExpressionInterface|SqlInterface $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right = null
    ) {
        $this->left  = $left;
        $this->right = $right;

        if ($operator !== self::OPERATOR_EQUAL_TO) {
            $this->operator = $operator;
        }
    }

    /**
     * Get left side of operator (wraps lazily if needed)
     */
    public function getLeft(): ?ArgumentInterface
    {
        if ($this->left === null) {
            return null;
        }

        if (! $this->left instanceof ArgumentInterface) {
            $this->left = $this->left instanceof ExpressionInterface || $this->left instanceof SqlInterface
                ? new Select($this->left)
                : new Identifier($this->left);
        }

        return $this->left;
    }

    /**
     * Set left side of operator
     */
    public function setLeft(string|ArgumentInterface|ExpressionInterface|SqlInterface $left): static
    {
        $this->left = $left;
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
     * Get right side of operator (wraps lazily if needed)
     */
    public function getRight(): ?ArgumentInterface
    {
        if ($this->right === null) {
            return null;
        }

        if (! $this->right instanceof ArgumentInterface) {
            $this->right = $this->right instanceof ExpressionInterface || $this->right instanceof SqlInterface
                ? new Select($this->right)
                : new Value($this->right);
        }

        return $this->right;
    }

    /**
     * Set right side of operator
     */
    public function setRight(
        null|bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right
    ): static {
        $this->right = $right;
        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $left  = $this->getLeft();
        $right = $this->getRight();

        if ($left === null) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if ($right === null) {
            throw new InvalidArgumentException('Right expression must be specified');
        }

        return [
            'spec'   => $this->specification ?? "%s {$this->operator} %s",
            'values' => [$left, $right],
        ];
    }

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if ($this->left === null) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if ($this->right === null) {
            throw new InvalidArgumentException('Right expression must be specified');
        }

        if ($this->left instanceof ArgumentInterface) {
            $leftSql = $this->left->toSql($builder);
        } elseif (is_string($this->left)) {
            $q       = $builder->q;
            $leftSql = str_contains($this->left, '.')
                ? $q . str_replace('.', $q . '.' . $q, $this->left) . $q
                : $q . $this->left . $q;
        } elseif ($this->left instanceof ExpressionInterface) {
            $leftSql = $builder->processExpression($this->left);
        } else {
            $leftSql = '(' . $builder->processSubSelect($this->left) . ')';
        }

        if ($this->right instanceof ArgumentInterface) {
            $rightSql = $this->right->toSql($builder);
        } elseif (is_scalar($this->right)) {
            $rightSql = $builder->bindValue($this->right);
        } elseif ($this->right instanceof ExpressionInterface) {
            $rightSql = $builder->processExpression($this->right);
        } else {
            $rightSql = '(' . $builder->processSubSelect($this->right) . ')';
        }

        return $leftSql . ' ' . $this->operator . ' ' . $rightSql;
    }
}
