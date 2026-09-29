<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;

class Like extends AbstractExpression implements PredicateInterface
{
    protected string             $operator   = 'LIKE';
    protected ?ArgumentInterface $identifier = null;
    protected ?ArgumentInterface $like       = null;

    /**
     * Constructor
     */
    public function __construct(
        string|ArgumentInterface|null $identifier = null,
        bool|float|int|string|ArgumentInterface|null $like = null,
    ) {
        if (null !== $identifier) {
            $this->setIdentifier($identifier);
        }

        if (null !== $like) {
            $this->setLike($like);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingIdentifier();
        }

        if (! $this->like instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingLikeExpression();
        }

        $identifierSpec = $this->identifier->getSpecification();
        $likeSpec       = $this->like->getSpecification();

        return [
            'spec'   => $this->specification ?? "{$identifierSpec} {$this->operator} {$likeSpec}",
            'values' => [$this->identifier, $this->like],
        ];
    }

    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    public function getLike(): ?ArgumentInterface
    {
        return $this->like;
    }

    /**
     * Set identifier for comparison
     */
    public function setIdentifier(string|ArgumentInterface $identifier): static
    {
        $this->identifier = $identifier instanceof ArgumentInterface
            ? $identifier
            : new Identifier($identifier);

        return $this;
    }

    /**
     * Set like pattern for comparison
     */
    public function setLike(bool|float|int|string|ArgumentInterface|null $like): static
    {
        $this->like = $like instanceof ArgumentInterface
            ? $like
            : new Value($like);

        return $this;
    }
}
