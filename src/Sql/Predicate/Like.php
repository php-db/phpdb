<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;

class Like extends AbstractExpression implements PredicateInterface
{
    protected string $specification      = '%1$s LIKE %2$s';
    protected ?ArgumentInterface $identifier = null;
    protected ?ArgumentInterface $like       = null;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|string|ArgumentInterface $like = null
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
    public function setIdentifier(string|ArgumentInterface $identifier): static
    {
        $this->identifier = $identifier instanceof ArgumentInterface
            ? $identifier
            : Argument::identifier($identifier);

        return $this;
    }

    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Set like pattern for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setLike(string|ArgumentInterface $like): static
    {
        $this->like = $like instanceof ArgumentInterface
            ? $like
            : Argument::value($like);

        return $this;
    }

    public function getLike(): ?ArgumentInterface
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
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->like instanceof ArgumentInterface) {
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
