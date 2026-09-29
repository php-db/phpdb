<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;

class IsNull extends AbstractExpression implements PredicateInterface
{
    protected string $operator = 'IS NULL';

    protected ?ArgumentInterface $identifier = null;

    /**
     * Constructor
     */
    public function __construct(string|ArgumentInterface|null $identifier = null)
    {
        if (null !== $identifier) {
            $this->setIdentifier($identifier);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingIdentifier();
        }

        $identifierSpec = $this->identifier->getSpecification();

        return [
            'spec'   => $this->specification ?? "{$identifierSpec} {$this->operator}",
            'values' => [$this->identifier],
        ];
    }

    /**
     * Get identifier of comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
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
}
