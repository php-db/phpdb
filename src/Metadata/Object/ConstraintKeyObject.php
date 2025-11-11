<?php

namespace PhpDb\Metadata\Object;

class ConstraintKeyObject
{
    public const FK_CASCADE     = 'CASCADE';
    public const FK_SET_NULL    = 'SET NULL';
    public const FK_NO_ACTION   = 'NO ACTION';
    public const FK_RESTRICT    = 'RESTRICT';
    public const FK_SET_DEFAULT = 'SET DEFAULT';

    /** @var string */
    protected $columnName;

    /** @var int */
    protected $ordinalPosition;

    /** @var bool */
    protected $positionInUniqueConstraint;

    /** @var string */
    protected $referencedTableSchema;

    /** @var string */
    protected $referencedTableName;

    /** @var string */
    protected $referencedColumnName;

    /** @var string */
    protected $foreignKeyUpdateRule;

    /** @var string */
    protected $foreignKeyDeleteRule;

    /**
     * Constructor
     *
     * @param string $column
     */
    public function __construct($column)
    {
        $this->setColumnName($column);
    }

    /**
     * Get column name
     *
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Set column name
     *
     * @param  string $columnName
     * @return $this Provides a fluent interface
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
        return $this;
    }

    /**
     * Get ordinal position
     *
     * @return int
     */
    public function getOrdinalPosition()
    {
        return $this->ordinalPosition;
    }

    /**
     * Set ordinal position
     *
     * @param  int $ordinalPosition
     * @return $this Provides a fluent interface
     */
    public function setOrdinalPosition($ordinalPosition)
    {
        $this->ordinalPosition = $ordinalPosition;
        return $this;
    }

    /**
     * Get referenced table name
     *
     * @return string
     */
    public function getReferencedTableName()
    {
        return $this->referencedTableName;
    }

    /**
     * Set Referenced table name
     *
     * @param  string $referencedTableName
     * @return $this Provides a fluent interface
     */
    public function setReferencedTableName($referencedTableName)
    {
        $this->referencedTableName = $referencedTableName;
        return $this;
    }

    /**
     * Get referenced column name
     *
     * @return string
     */
    public function getReferencedColumnName()
    {
        return $this->referencedColumnName;
    }

    /**
     * Set referenced column name
     *
     * @param  string $referencedColumnName
     * @return $this Provides a fluent interface
     */
    public function setReferencedColumnName($referencedColumnName)
    {
        $this->referencedColumnName = $referencedColumnName;
        return $this;
    }

    /**
     * set foreign key update rule
     *
     * @param string $foreignKeyUpdateRule
     */
    public function setForeignKeyUpdateRule($foreignKeyUpdateRule): void
    {
        $this->foreignKeyUpdateRule = $foreignKeyUpdateRule;
    }

    /**
     * Get foreign key update rule
     *
     * @return string
     */
    public function getForeignKeyUpdateRule()
    {
        return $this->foreignKeyUpdateRule;
    }

    /**
     * Set foreign key delete rule
     *
     * @param string $foreignKeyDeleteRule
     */
    public function setForeignKeyDeleteRule($foreignKeyDeleteRule): void
    {
        $this->foreignKeyDeleteRule = $foreignKeyDeleteRule;
    }

    /**
     * get foreign key delete rule
     *
     * @return string
     */
    public function getForeignKeyDeleteRule()
    {
        return $this->foreignKeyDeleteRule;
    }
}
