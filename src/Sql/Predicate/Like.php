<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\PreparableSqlBuilder;

class Like extends AbstractExpression implements PredicateInterface
{
    protected string $operator               = 'LIKE';
    protected ?ArgumentInterface $identifier = null;
    protected ?ArgumentInterface $like       = null;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|bool|float|int|string|ArgumentInterface $like = null
    ) {
        if ($identifier !== null) {
            $this->identifier = $identifier instanceof ArgumentInterface
                ? $identifier
                : new Identifier($identifier);
        }

        if ($like !== null) {
            $this->like = $like instanceof ArgumentInterface
                ? $like
                : new Value($like);
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

    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Set like pattern for comparison
     */
    public function setLike(bool|float|int|null|string|ArgumentInterface $like): static
    {
        $this->like = $like instanceof ArgumentInterface
            ? $like
            : new Value($like);

        return $this;
    }

    public function getLike(): ?ArgumentInterface
    {
        return $this->like;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->like instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Like expression must be specified');
        }

        return [
            'spec'   => $this->specification ?? "%s {$this->operator} %s",
            'values' => [$this->identifier, $this->like],
        ];
    }

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->like instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Like expression must be specified');
        }

        $identifierSql = $builder->argumentToSql($this->identifier);
        $likeSql       = $builder->argumentToSql($this->like);

        return "{$identifierSql} {$this->operator} {$likeSql}";
    }
}
