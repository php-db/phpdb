<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

abstract class AbstractTableObject
{
    protected ?string $name = null;

    protected ?string $type = null;

    /** @var array<int|string, mixed>|null */
    protected ?array $columns = null;

    /** @var array<int|string, mixed>|null */
    protected ?array $constraints = null;

    /**
     * Constructor
     */
    public function __construct(?string $name = null)
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
     * Get columns
     *
     * @return array<int|string, mixed>|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * Set constraints
     */
    public function setConstraints(array $constraints): void
    {
        $this->constraints = $constraints;
    }

    /**
     * Get constraints
     *
     * @return array<int|string, mixed>|null
     */
    public function getConstraints(): ?array
    {
        return $this->constraints;
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
    public function getName(): ?string
    {
        return $this->name;
    }
}
