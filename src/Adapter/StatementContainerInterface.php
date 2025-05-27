<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter;

interface StatementContainerInterface
{
    public function setSql(string|null $sql): static;

    public function getSql(): string|null;

    public function setParameterContainer(ParameterContainer $parameterContainer): static;

    public function getParameterContainer(): ParameterContainer|null;
}
