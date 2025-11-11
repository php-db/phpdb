<?php

namespace PhpDb\Sql\Platform\Sqlite;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Select;
use Override;

class SelectDecorator extends Select implements PlatformDecoratorInterface
{
    public $subject;

    /**
     * Set Subject
     *
     * @param Select $subject
     * @return $this Provides a fluent interface
     */
    #[Override] public function setSubject($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    protected function localizeVariables(): void
    {
        parent::localizeVariables();
        $this->specifications[self::COMBINE] = '%1$s %2$s';
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    protected function processStatementStart(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return '';
    }

    #[\Override]
    protected function processLimit(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?array {
        if ($this->limit === null && $this->offset !== null) {
            return [''];
        }
        if ($this->limit === null) {
            return null;
        }
        if ($parameterContainer instanceof \PhpDb\Adapter\ParameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet($paramPrefix . 'limit', $this->limit, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName('limit')];
        }

        return [$this->limit];
    }

    #[\Override]
    protected function processOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?array {
        if ($this->offset === null) {
            return null;
        }
        if ($parameterContainer instanceof \PhpDb\Adapter\ParameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet($paramPrefix . 'offset', $this->offset, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName('offset')];
        }

        return [$this->offset];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    protected function processStatementEnd(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return '';
    }
}
