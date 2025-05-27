<?php

namespace Laminas\Db\Adapter;

class StatementContainer implements StatementContainerInterface
{
    /** @var string */
    protected $sql = '';

    /** @var ParameterContainer */
    protected $parameterContainer;

    public function __construct(string|null $sql = null, ?ParameterContainer $parameterContainer = null)
    {
        if ($sql) {
            $this->setSql($sql);
        }
        $this->parameterContainer = $parameterContainer ?: new ParameterContainer();
    }

    public function setSql(string|null $sql): static
    {
        $this->sql = $sql;
        return $this;
    }

    public function getSql(): string|null
    {
        return $this->sql;
    }

    public function setParameterContainer(ParameterContainer $parameterContainer): static
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    public function getParameterContainer(): ParameterContainer|null
    {
        return $this->parameterContainer;
    }
}
