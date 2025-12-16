<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\PreparableSqlBuilder;

class IsNull extends AbstractExpression implements PredicateInterface
{
    protected string $operator = 'IS NULL';

    protected ?ArgumentInterface $identifier = null;

    /**
     * Constructor
     */
    public function __construct(null|string|ArgumentInterface $identifier = null)
    {
        if ($identifier !== null) {
            $this->identifier = $identifier instanceof ArgumentInterface
                ? $identifier
                : new Identifier($identifier);
        }
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
     * Get identifier of comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        return [
            'spec'   => $this->specification ?? "%s {$this->operator}",
            'values' => [$this->identifier],
        ];
    }

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        $identifierSql = $builder->argumentToSql($this->identifier);

        return "{$identifierSql} {$this->operator}";
    }
}
