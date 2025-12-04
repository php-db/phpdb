<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

class ConstraintObject
{
    protected string $name;

    protected string $tableName;

    protected ?string $schemaName = null;

    /**
     * One of "PRIMARY KEY", "UNIQUE", "FOREIGN KEY", or "CHECK"
     */
    protected ?string $type = null;

    /** @var string[] */
    protected array $columns = [];

    protected ?string $referencedTableSchema = null;

    protected ?string $referencedTableName = null;

    /** @var string[]|null */
    protected ?array $referencedColumns = null;

    protected ?string $matchOption = null;

    protected ?string $updateRule = null;

    protected ?string $deleteRule = null;

    protected ?string $checkClause = null;

    /**
     * Constructor
     */
    public function __construct(string $name, string $tableName, ?string $schemaName = null)
    {
        $this->setName($name);
        $this->setTableName($tableName);

        if ($schemaName !== null) {
            $this->setSchemaName($schemaName);
        }
    }

    /**
     * Set name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set schema name
     */
    public function setSchemaName(string $schemaName): void
    {
        $this->schemaName = $schemaName;
    }

    /**
     * Get schema name
     */
    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }

    /**
     * Get table name
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Set table name
     */
    public function setTableName(string $tableName): static
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Set type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function hasColumns(): bool
    {
        return $this->columns !== [];
    }

    /**
     * Get Columns.
     *
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set Columns.
     *
     * @param string[] $columns
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get Referenced Table Schema.
     */
    public function getReferencedTableSchema(): ?string
    {
        return $this->referencedTableSchema;
    }

    /**
     * Set Referenced Table Schema.
     */
    public function setReferencedTableSchema(string $referencedTableSchema): static
    {
        $this->referencedTableSchema = $referencedTableSchema;
        return $this;
    }

    /**
     * Get Referenced Table Name.
     */
    public function getReferencedTableName(): ?string
    {
        return $this->referencedTableName;
    }

    /**
     * Set Referenced Table Name.
     */
    public function setReferencedTableName(string $referencedTableName): static
    {
        $this->referencedTableName = $referencedTableName;
        return $this;
    }

    /**
     * Get Referenced Columns.
     *
     * @return string[]|null
     */
    public function getReferencedColumns(): ?array
    {
        return $this->referencedColumns;
    }

    /**
     * Set Referenced Columns.
     *
     * @param string[] $referencedColumns
     */
    public function setReferencedColumns(array $referencedColumns): static
    {
        $this->referencedColumns = $referencedColumns;
        return $this;
    }

    /**
     * Get Match Option.
     */
    public function getMatchOption(): ?string
    {
        return $this->matchOption;
    }

    /**
     * Set Match Option.
     */
    public function setMatchOption(string $matchOption): static
    {
        $this->matchOption = $matchOption;
        return $this;
    }

    /**
     * Get Update Rule.
     */
    public function getUpdateRule(): ?string
    {
        return $this->updateRule;
    }

    /**
     * Set Update Rule.
     */
    public function setUpdateRule(string $updateRule): static
    {
        $this->updateRule = $updateRule;
        return $this;
    }

    /**
     * Get Delete Rule.
     */
    public function getDeleteRule(): ?string
    {
        return $this->deleteRule;
    }

    /**
     * Set Delete Rule.
     */
    public function setDeleteRule(string $deleteRule): static
    {
        $this->deleteRule = $deleteRule;
        return $this;
    }

    /**
     * Get Check Clause.
     */
    public function getCheckClause(): ?string
    {
        return $this->checkClause;
    }

    /**
     * Set Check Clause.
     */
    public function setCheckClause(string $checkClause): static
    {
        $this->checkClause = $checkClause;
        return $this;
    }

    /**
     * Is primary key
     */
    public function isPrimaryKey(): bool
    {
        return 'PRIMARY KEY' === $this->type;
    }

    /**
     * Is unique key
     */
    public function isUnique(): bool
    {
        return 'UNIQUE' === $this->type;
    }

    /**
     * Is foreign key
     */
    public function isForeignKey(): bool
    {
        return 'FOREIGN KEY' === $this->type;
    }

    /**
     * Is foreign key
     */
    public function isCheck(): bool
    {
        return 'CHECK' === $this->type;
    }
}
