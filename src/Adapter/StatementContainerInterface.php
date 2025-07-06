<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

interface StatementContainerInterface
{
    /** Set sql */
    public function setSql(?string $sql): StatementContainerInterface;

    /** Get sql */
    public function getSql(): ?string;

    /** Set parameter container */
    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface;

    /** Get parameter container */
    public function getParameterContainer(): ?ParameterContainer;
}
