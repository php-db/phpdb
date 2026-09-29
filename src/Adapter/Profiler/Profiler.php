<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

use Override;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\ParameterContainer;
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

    /**
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function profilerFinish(): ProfilerInterface
    {
        if (! isset($this->profiles[$this->currentIndex])) {
            throw Exception\RuntimeException::forUnstartedProfile(__FUNCTION__);
        }
        $current           = &$this->profiles[$this->currentIndex];
        $current['end']    = microtime(true);
        $current['elapse'] = $current['end'] - $current['start'];
        $this->currentIndex++;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function profilerStart(string|StatementContainerInterface $target): ProfilerInterface
    {
        $profileInformation = [
            'sql'        => '',
            'parameters' => null,
            'start'      => microtime(true),
            'end'        => null,
            'elapse'     => null,
        ];

        if (is_string($target)) {
            $profileInformation['sql'] = $target;
        } else {
            $profileInformation['sql'] = $target->getSql();
            $container                 = $target->getParameterContainer();
            if (null !== $container) {
                $profileInformation['parameters'] = clone $container;
            }
        }

        $this->profiles[$this->currentIndex] = $profileInformation;

        return $this;
    }
}
