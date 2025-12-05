<?php

namespace PhpDb\Adapter;

class StatementContainer implements StatementContainerInterface
{
    protected string $sql = '';

    protected ?ParameterContainer $parameterContainer = null;

    public function __construct(?string $sql = null, ?ParameterContainer $parameterContainer = null)
    {
        if ($sql) {
            $this->setSql($sql);
        }
        $this->parameterContainer = $parameterContainer;
    }

    /**
     * @param string $sql
     */
    public function setSql($sql): StatementContainerInterface
    {
        $this->sql = $sql;
        return $this;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    public function getParameterContainer(): ?ParameterContainer
    {
        return $this->parameterContainer;
    }
}
