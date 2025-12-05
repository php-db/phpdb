<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

class ViewObject extends AbstractTableObject
{
    protected ?string $viewDefinition = null;

    protected ?string $checkOption = null;

    protected ?bool $isUpdatable = null;

    public function getViewDefinition(): ?string
    {
        return $this->viewDefinition;
    }

    /**
     * @param null|string $viewDefinition to set
     */
    public function setViewDefinition(?string $viewDefinition): static
    {
        $this->viewDefinition = $viewDefinition;
        return $this;
    }

    public function getCheckOption(): ?string
    {
        return $this->checkOption;
    }

    /**
     * @param null|string $checkOption to set
     */
    public function setCheckOption(?string $checkOption): static
    {
        $this->checkOption = $checkOption;
        return $this;
    }

    public function getIsUpdatable(): ?bool
    {
        return $this->isUpdatable;
    }

    public function isUpdatable(): ?bool
    {
        return $this->isUpdatable;
    }

    /**
     * @param bool $isUpdatable to set
     */
    public function setIsUpdatable(?bool $isUpdatable): static
    {
        $this->isUpdatable = $isUpdatable;
        return $this;
    }
}
