<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

final class ConstraintKeyObject
{
    final public const string FK_CASCADE = 'CASCADE';

    final public const string FK_SET_NULL = 'SET NULL';

    final public const string FK_NO_ACTION = 'NO ACTION';

    final public const string FK_RESTRICT = 'RESTRICT';

    final public const string FK_SET_DEFAULT = 'SET DEFAULT';

    private ?int $ordinalPosition = null;

    private ?bool $positionInUniqueConstraint = null;

    private ?string $referencedTableSchema = null;

    private ?string $referencedTableName = null;

    private ?string $referencedColumnName = null;

    private ?string $foreignKeyUpdateRule = null;

    private ?string $foreignKeyDeleteRule = null;

    /**
     * Constructor
     */
    public function __construct(
        private string $columnName,
    ) {}

    /**
     * Get column name
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * get foreign key delete rule
     */
    public function getForeignKeyDeleteRule(): ?string
    {
        return $this->foreignKeyDeleteRule;
    }

    /**
     * Get foreign key update rule
     */
    public function getForeignKeyUpdateRule(): ?string
    {
        return $this->foreignKeyUpdateRule;
    }

    /**
     * Get ordinal position
     */
    public function getOrdinalPosition(): ?int
    {
        return $this->ordinalPosition;
    }

    /**
     * Get position in unique constraint
     */
    public function getPositionInUniqueConstraint(): ?bool
    {
        return $this->positionInUniqueConstraint;
    }

    /**
     * Get referenced column name
     */
    public function getReferencedColumnName(): ?string
    {
        return $this->referencedColumnName;
    }

    /**
     * Get referenced table name
     */
    public function getReferencedTableName(): ?string
    {
        return $this->referencedTableName;
    }

    /**
     * Get referenced table schema
     */
    public function getReferencedTableSchema(): ?string
    {
        return $this->referencedTableSchema;
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
     * Set foreign key delete rule
     */
    public function setForeignKeyDeleteRule(string $foreignKeyDeleteRule): void
    {
        $this->foreignKeyDeleteRule = $foreignKeyDeleteRule;
    }

    /**
     * set foreign key update rule
     */
    public function setForeignKeyUpdateRule(string $foreignKeyUpdateRule): void
    {
        $this->foreignKeyUpdateRule = $foreignKeyUpdateRule;
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
     * Set position in unique constraint
     */
    public function setPositionInUniqueConstraint(bool $positionInUniqueConstraint): static
    {
        $this->positionInUniqueConstraint = $positionInUniqueConstraint;
        return $this;
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
     * Set Referenced table name
     */
    public function setReferencedTableName(string $referencedTableName): static
    {
        $this->referencedTableName = $referencedTableName;
        return $this;
    }

    /**
     * Set referenced table schema
     */
    public function setReferencedTableSchema(string $referencedTableSchema): static
    {
        $this->referencedTableSchema = $referencedTableSchema;
        return $this;
    }
}
