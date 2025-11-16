<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;

class Like extends AbstractExpression implements PredicateInterface
{
    protected string $specification = '%1$s LIKE %2$s';

    protected ?Argument $identifier = null;

    protected ?Argument $like = null;

    /**
     * Constructor
     */
    public function __construct(
        null|float|int|string|array|Argument $identifier = null,
        null|float|int|string|array|Argument $like = null
    ) {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
        }

        if ($like !== null) {
            $this->setLike($like);
        }
    }

    /**
     * Set identifier for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setIdentifier(
        null|string|int|float|array|Argument $value,
        ArgumentType $type = ArgumentType::Identifier
    ): static {
        $this->identifier = $value instanceof Argument ? $value : new Argument($value, $type);

        return $this;
    }

    public function getIdentifier(): ?Argument
    {
        return $this->identifier;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setLike(
        null|string|int|float|array|Argument $like,
        ArgumentType $type = ArgumentType::Value
    ): static {
        $this->like = $like instanceof Argument ? $like : new Argument($like, $type);

        return $this;
    }

    public function getLike(): ?Argument
    {
        return $this->like;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setSpecification(string $specification): static
    {
        $this->specification = $specification;

        return $this;
    }

    public function getSpecification(): string
    {
        return $this->specification;
    }

    #[Override]
    public function getExpressionData(): ExpressionData
    {
        if (! $this->identifier instanceof Argument) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->like instanceof Argument) {
            throw new InvalidArgumentException('Like expression must be specified');
        }

        return new ExpressionData(
            $this->getSpecification(),
            [
                $this->identifier,
                $this->like,
            ]
        );
    }
}
