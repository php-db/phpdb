<?php

namespace Laminas\Db\Sql\Predicate;

use Laminas\Db\Sql\AbstractExpression;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Override;

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

    /**
     * {@inheritDoc}
     */
    protected $allowedTypes = [
        self::TYPE_IDENTIFIER,
        self::TYPE_VALUE,
    ];

    protected ?Argument $left = null;

    protected ?Argument $right = null;

    protected string $operator = self::OPERATOR_EQUAL_TO;

    /**
     * Constructor
     */
    public function __construct(
        null|string|int|float|Argument|Expression $left = null,
        string $operator = self::OPERATOR_EQUAL_TO,
        null|string|int|float|Argument|Expression $right = null
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
     * Set left side of operator
     *
     * @return $this Provides a fluent interface
     */
    public function setLeft(
        null|string|int|float|Expression|Argument $left,
        ArgumentType $type = ArgumentType::Identifier
    ): static {
        $this->left = $left instanceof Argument ? $left : new Argument($left, $type);

        return $this;
    }

    /**
     * Get left side of operator
     */
    public function getLeft(): ?Argument
    {
        return $this->left;
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
     * Get operator string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Set right side of operator
     *
     * @return $this Provides a fluent interface
     */
    public function setRight(
        null|string|int|float|Expression|Argument $right,
        ArgumentType $type = ArgumentType::Value
    ): static {
        $this->right = $right instanceof Argument ? $right : new Argument($right, $type);

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
     * Get predicate parts for where statement
     */
    #[Override]
    public function getExpressionData(): array
    {
        if ($this->left === null) {
            throw new InvalidArgumentException('Left expression must be specified');
        }

        if ($this->right === null) {
            throw new InvalidArgumentException('Right expression must be specified');
        }

        $values = [$this->left, $this->right];

        return [
            [
                '%s ' . $this->operator . ' %s',
                $values,
            ],
        ];
    }
}
