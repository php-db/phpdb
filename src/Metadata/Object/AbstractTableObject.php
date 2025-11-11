<?php

namespace PhpDb\Metadata\Object;

abstract class AbstractTableObject
{
    /*
    protected $catalogName = null;
    protected $schemaName = null;
    */

    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /** @var array */
    protected $columns;

    /** @var array */
    protected $constraints;

    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct($name)
    {
        if ($name) {
            $this->setName($name);
        }
    }

    /**
     * Set columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * Set constraints
     *
     * @param array $constraints
     */
    public function setConstraints($constraints): void
    {
        $this->constraints = $constraints;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }
}
