<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

class ConstraintKeyObject
{
    final public const FK_CASCADE = 'CASCADE';

    final public const FK_SET_NULL = 'SET NULL';

    final public const FK_NO_ACTION = 'NO ACTION';

    final public const FK_RESTRICT = 'RESTRICT';

    final public const FK_SET_DEFAULT = 'SET DEFAULT';

    protected ?int $ordinalPosition = null;

    protected ?bool $positionInUniqueConstraint = null;

    protected ?string $referencedTableSchema = null;

    protected ?string $referencedTableName = null;

    protected ?string $referencedColumnName = null;

    protected ?string $foreignKeyUpdateRule = null;

    protected ?string $foreignKeyDeleteRule = null;

    /**
     * Constructor
     */
    public function __construct(protected string $columnName)
    {
    }

    /**
     * Get column name
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * Set column name
     */
    public function setColumnName(string $columnName): static
    {
        $this->columnName = $columnName;
        return $this;
    }

    /**
     * Get ordinal position
     */
    public function getOrdinalPosition(): ?int
    {
        return $this->ordinalPosition;
    }

    /**
     * Set ordinal position
     */
    public function setOrdinalPosition(int $ordinalPosition): static
    {
        $this->ordinalPosition = $ordinalPosition;
        return $this;
    }

    /**
     * Get position in unique constraint
     */
    public function getPositionInUniqueConstraint(): ?bool
    {
        return $this->positionInUniqueConstraint;
    }

    /**
     * Set position in unique constraint
     */
    public function setPositionInUniqueConstraint(bool $positionInUniqueConstraint): static
    {
        $this->positionInUniqueConstraint = $positionInUniqueConstraint;
        return $this;
    }

    /**
     * Get referenced table schema
     */
    public function getReferencedTableSchema(): ?string
    {
        return $this->referencedTableSchema;
    }

    /**
     * Set referenced table schema
     */
    public function setReferencedTableSchema(string $referencedTableSchema): static
    {
        $this->referencedTableSchema = $referencedTableSchema;
        return $this;
    }

    /**
     * Get referenced table name
     */
    public function getReferencedTableName(): ?string
    {
        return $this->referencedTableName;
    }

    /**
     * Set Referenced table name
     */
    public function setReferencedTableName(string $referencedTableName): static
    {
        $this->referencedTableName = $referencedTableName;
        return $this;
    }

    /**
     * Get referenced column name
     */
    public function getReferencedColumnName(): ?string
    {
        return $this->referencedColumnName;
    }

    /**
     * Set referenced column name
     */
    public function setReferencedColumnName(string $referencedColumnName): static
    {
        $this->referencedColumnName = $referencedColumnName;
        return $this;
    }

    /**
     * set foreign key update rule
     */
    public function setForeignKeyUpdateRule(string $foreignKeyUpdateRule): void
    {
        $this->foreignKeyUpdateRule = $foreignKeyUpdateRule;
    }

    /**
     * Get foreign key update rule
     */
    public function getForeignKeyUpdateRule(): ?string
    {
        return $this->foreignKeyUpdateRule;
    }

    /**
     * Set foreign key delete rule
     */
    public function setForeignKeyDeleteRule(string $foreignKeyDeleteRule): void
    {
        $this->foreignKeyDeleteRule = $foreignKeyDeleteRule;
    }

    /**
     * get foreign key delete rule
     */
    public function getForeignKeyDeleteRule(): ?string
    {
        return $this->foreignKeyDeleteRule;
    }
}
