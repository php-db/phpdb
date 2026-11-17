<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

/**
 * @api
 */
interface StatementContainerInterface
{
    /** Get parameter container */
    public function getParameterContainer(): ?ParameterContainer;

    /** Get sql */
    public function getSql(): ?string;

    /** Set parameter container */
    public function setParameterContainer(ParameterContainer $parameterContainer): self;

    /** Set sql */
    public function setSql(?string $sql): self;
}
