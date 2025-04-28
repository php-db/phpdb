<?php

namespace Laminas\Db\Metadata\Object;

class ViewObject extends AbstractTableObject
{
    /** @var null|string */
    protected $viewDefinition;

    /** @var null|string */
    protected $checkOption;

    /** @var null|bool */
    protected $isUpdatable;

    /**
     * @param string $viewDefinition to set
     * @return $this Provides a fluent interface
     */
    public function setViewDefinition($viewDefinition)
    {
        $this->viewDefinition = $viewDefinition;
        return $this;
    }

    /**
     * @param string $checkOption to set
     * @return $this Provides a fluent interface
     */
    public function setCheckOption($checkOption)
    {
        $this->checkOption = $checkOption;
        return $this;
    }

    /**
     * @param bool $isUpdatable to set
     * @return $this Provides a fluent interface
     */
    public function setIsUpdatable($isUpdatable)
    {
        $this->isUpdatable = $isUpdatable;
        return $this;
    }
}
