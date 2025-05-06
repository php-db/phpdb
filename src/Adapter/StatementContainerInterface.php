<?php

namespace Laminas\Db\Adapter;

interface StatementContainerInterface
{
    /**
     * Set sql
     *
     * @param null|string $sql
     * @return static
     */
    public function setSql($sql): static;

    /**
     * Get sql
     *
     * @return null|string
     */
    public function getSql(): ?string;

    /**
     * Set parameter container
     *
     * @return static
     */
    public function setParameterContainer(ParameterContainer $parameterContainer);

    /**
     * Get parameter container
     *
     * @return null|ParameterContainer
     */
    public function getParameterContainer();
}
