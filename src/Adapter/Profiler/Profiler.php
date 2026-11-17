<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

use Override;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

use function array_key_exists;
use function array_key_last;
use function is_string;
use function microtime;

/**
 * @api
 *
 * @phpstan-type ProfileShape array{
 *     sql: string|null,
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
        $lastIndex = array_key_last($this->profiles);

        return null === $lastIndex ? null : $this->profiles[$lastIndex] ?? null;
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
     *
     * @throws Exception\RuntimeException When no profile has been started.
     */
    #[Override]
    public function profilerFinish(): ProfilerInterface
    {
        if (! array_key_exists($this->currentIndex, $this->profiles)) {
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
        $start     = microtime(true);
        $container = is_string($target) ? null : $target->getParameterContainer();

        $this->profiles[$this->currentIndex] = [
            'sql'        => is_string($target) ? $target : $target->getSql(),
            'parameters' => null === $container ? null : clone $container,
            'start'      => $start,
            'end'        => null,
            'elapse'     => null,
        ];

        return $this;
    }
}
