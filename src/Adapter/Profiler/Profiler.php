<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\StatementContainerInterface;

use function end;
use function is_string;
use function microtime;

/**
 * @phpstan-type ProfileShape array{
 *     sql: string,
 *     parameters: ParameterContainer|null,
 *     start: float,
 *     end: float|null,
 *     elapse: float|null,
 * }
 * @phpstan-type ProfilesShape ProfileShape[]
 */
class Profiler implements ProfilerInterface
{
    /** @var ProfilesShape */
    protected $profiles = [];

    /** @var int */
    protected $currentIndex = 0;

    /**
     * @throws InvalidArgumentException
     * @return $this Provides a fluent interface
     */
    public function profilerStart(string|StatementContainerInterface $target): ProfilerInterface
    {
        $profileInformation = [
            'sql'        => '',
            'parameters' => null,
            'start'      => microtime(true),
            'end'        => null,
            'elapse'     => null,
        ];

        if ($target instanceof StatementContainerInterface) {
            $profileInformation['sql'] = $target->getSql();
            $container                 = $target->getParameterContainer();
            if ($container !== null) {
                $profileInformation['parameters'] = clone $container;
            }
        } elseif (is_string($target)) {
            $profileInformation['sql'] = $target;
        }

        $this->profiles[$this->currentIndex] = $profileInformation;

        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function profilerFinish(): ProfilerInterface
    {
        if (! isset($this->profiles[$this->currentIndex])) {
            throw new Exception\RuntimeException(
                'A profile must be started before ' . __FUNCTION__ . ' can be called.'
            );
        }
        $current           = &$this->profiles[$this->currentIndex];
        $current['end']    = microtime(true);
        $current['elapse'] = $current['end'] - $current['start'];
        $this->currentIndex++;
        return $this;
    }

    /**
     * @return ProfileShape|null
     */
    public function getLastProfile(): ?array
    {
        return end($this->profiles);
    }

    /**
     * @return ProfilesShape
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }
}
