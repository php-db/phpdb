<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

class ViewObject extends AbstractTableObject
{
    /** @var null|string */
    protected $viewDefinition;

    /** @var null|string */
    protected $checkOption;

    /** @var null|bool */
    protected $isUpdatable;

    /**
     * @return null|string
     */
    public function getViewDefinition()
    {
        return $this->viewDefinition;
    }

    /**
     * @param string $viewDefinition to set
     * @return $this Provides a fluent interface
     */
    public function setViewDefinition($viewDefinition): static
    {
        $this->viewDefinition = $viewDefinition;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCheckOption()
    {
        return $this->checkOption;
    }

    /**
     * @param string $checkOption to set
     * @return $this Provides a fluent interface
     */
    public function setCheckOption($checkOption): static
    {
        $this->checkOption = $checkOption;
        return $this;
    }

    /**
     * @return null|bool
     */
    public function getIsUpdatable()
    {
        return $this->isUpdatable;
    }

    /**
     * @return null|bool
     */
    public function isUpdatable()
    {
        return $this->isUpdatable;
    }

    /**
     * @param bool $isUpdatable to set
     * @return $this Provides a fluent interface
     */
    public function setIsUpdatable($isUpdatable): static
    {
        $this->isUpdatable = $isUpdatable;
        return $this;
    }
}
