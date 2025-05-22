<?php

namespace Laminas\Db\Adapter;

interface StatementContainerInterface
{
    /** Set sql */
    public function setSql(?string $sql): static;

    /** Get sql */
    public function getSql(): ?string;

    /** Set parameter container */
    public function setParameterContainer(ParameterContainer $parameterContainer): static;

    /** Get parameter container */
    public function getParameterContainer(): ?ParameterContainer;
}
