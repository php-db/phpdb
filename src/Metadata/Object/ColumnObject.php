<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

use function array_key_exists;

class ColumnObject
{
    protected string $name;

    protected string $tableName;

    protected ?string $schemaName = null;

    protected ?int $ordinalPosition = null;

    protected null|string|int|bool $columnDefault = null;

    protected ?bool $isNullable = null;

    protected ?string $dataType = null;

    protected ?int $characterMaximumLength = null;

    protected ?int $characterOctetLength = null;

    protected ?int $numericPrecision = null;

    protected ?int $numericScale = null;

    protected ?bool $numericUnsigned = null;

    protected array $errata = [];

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
     * @return int|null $ordinalPosition
     */
    public function getOrdinalPosition(): ?int
    {
        return $this->ordinalPosition;
    }

    public function setOrdinalPosition(?int $ordinalPosition): static
    {
        $this->ordinalPosition = $ordinalPosition;
        return $this;
    }

    /**
     * @return null|string the $columnDefault
     */
    public function getColumnDefault(): ?string
    {
        return $this->columnDefault;
    }

    public function setColumnDefault(null|string|int|bool $columnDefault): static
    {
        $this->columnDefault = $columnDefault;
        return $this;
    }

    /**
     * @return bool|null $isNullable
     */
    public function getIsNullable(): ?bool
    {
        return $this->isNullable;
    }

    public function setIsNullable(?bool $isNullable): static
    {
        $this->isNullable = $isNullable;
        return $this;
    }

    /**
     * @return null|string the $dataType
     */
    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): static
    {
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * @return int|null the $characterMaximumLength
     */
    public function getCharacterMaximumLength(): ?int
    {
        return $this->characterMaximumLength;
    }

    public function setCharacterMaximumLength(?int $characterMaximumLength): static
    {
        $this->characterMaximumLength = $characterMaximumLength;
        return $this;
    }

    /**
     * @return int|null the $characterOctetLength
     */
    public function getCharacterOctetLength(): ?int
    {
        return $this->characterOctetLength;
    }

    public function setCharacterOctetLength(?int $characterOctetLength): static
    {
        $this->characterOctetLength = $characterOctetLength;
        return $this;
    }

    /**
     * @return int|null the $numericPrecision
     */
    public function getNumericPrecision(): ?int
    {
        return $this->numericPrecision;
    }

    public function setNumericPrecision(?int $numericPrecision): static
    {
        $this->numericPrecision = $numericPrecision;
        return $this;
    }

    /**
     * @return int|null the $numericScale
     */
    public function getNumericScale(): ?int
    {
        return $this->numericScale;
    }

    public function setNumericScale(?int $numericScale): static
    {
        $this->numericScale = $numericScale;
        return $this;
    }

    public function getNumericUnsigned(): ?bool
    {
        return $this->numericUnsigned;
    }

    public function setNumericUnsigned(?bool $numericUnsigned): static
    {
        $this->numericUnsigned = $numericUnsigned;
        return $this;
    }

    public function isNumericUnsigned(): ?bool
    {
        return $this->numericUnsigned;
    }

    /**
     * @return array the $errata
     */
    public function getErratas(): array
    {
        return $this->errata;
    }

    public function setErratas(array $erratas): static
    {
        foreach ($erratas as $name => $value) {
            $this->setErrata($name, $value);
        }

        return $this;
    }

    public function getErrata(string $errataName): mixed
    {
        if (array_key_exists($errataName, $this->errata)) {
            return $this->errata[$errataName];
        }

        return null;
    }

    public function setErrata(string $errataName, mixed $errataValue): static
    {
        $this->errata[$errataName] = $errataValue;
        return $this;
    }
}
