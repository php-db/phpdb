<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;

class IsNull extends AbstractExpression implements PredicateInterface
{
    protected string $specification = '%1$s IS NULL';

    protected ?Argument $identifier = null;

    /**
     * Constructor
     */
    public function __construct(null|float|int|string|array|Argument $identifier = null)
    {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
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

    /**
     * Get identifier of comparison
     */
    public function getIdentifier(): ?Argument
    {
        return $this->identifier;
    }

    /**
     * Set specification string to use in forming SQL predicate
     *
     * @return $this Provides a fluent interface
     */
    public function setSpecification(string $specification): static
    {
        $this->specification = $specification;
        return $this;
    }

    /**
     * Get specification string to use in forming SQL predicate
     */
    public function getSpecification(): string
    {
        return $this->specification;
    }

    /**
     * Get parts for where statement
     */
    #[Override]
    public function getExpressionData(): ExpressionData
    {
        if (! $this->identifier instanceof Argument) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        return new ExpressionData(
            $this->getSpecification(),
            [
                $this->identifier,
            ]
        );
    }
}
