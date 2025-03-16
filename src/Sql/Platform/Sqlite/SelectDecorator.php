<?php

namespace Laminas\Db\Sql\Platform\Sqlite;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Platform\PlatformDecoratorInterface;
use Laminas\Db\Sql\Select;

class SelectDecorator extends Select implements PlatformDecoratorInterface
{
    protected Select $subject;

    /**
     * Set Subject
     *
     * @param Select $subject
     * @return $this Provides a fluent interface
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function localizeVariables(): void
    {
        parent::localizeVariables();
        $this->specifications[self::COMBINE] = '%1$s %2$s';
    }

    /**
     * {@inheritDoc}
     */
    protected function processStatementStart(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return '';
    }

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
        if ($parameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet($paramPrefix . 'limit', $this->limit, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName('limit')];
        }

        return [$this->limit];
    }

    protected function processOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?array {
        if ($this->offset === null) {
            return null;
        }
        if ($parameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet($paramPrefix . 'offset', $this->offset, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName('offset')];
        }

        return [$this->offset];
    }

    /**
     * {@inheritDoc}
     */
    protected function processStatementEnd(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return '';
    }
}
