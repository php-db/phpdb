<?php

namespace PhpDb\Sql\Platform\Mysql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Select;
use Override;

class SelectDecorator extends Select implements PlatformDecoratorInterface
{
    /** @var Select */
    public $subject;

    /**
     * @param Select $subject
     *
     * @return void
     */
    #[Override] public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return void
     */
    #[\Override]
    protected function localizeVariables()
    {
        parent::localizeVariables();
        if ($this->limit === null && $this->offset !== null) {
            $this->specifications[self::LIMIT] = 'LIMIT 18446744073709551615';
        }
    }

    /** @return null|string[] */
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
            return [$driver->formatParameterName($paramPrefix . 'limit')];
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
            return [$driver->formatParameterName($paramPrefix . 'offset')];
        }

        return [$this->offset];
    }
}
