<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

use Override;

/**
 * @api
 */
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

    #[Override]
    public function getParameterContainer(): ?ParameterContainer
    {
        return $this->parameterContainer;
    }

    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    #[Override]
    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    /**
     * @param ?string $sql A null SQL string is stored as an empty one.
     */
    #[Override]
    public function setSql($sql): StatementContainerInterface
    {
        $this->sql = $sql ?? '';
        return $this;
    }
}
