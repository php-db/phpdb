<?php

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\StatementContainerInterface;

use function end;
use function microtime;

class Profiler implements ProfilerInterface
{
    protected array $profiles     = [];
    protected int   $currentIndex = 0;

    /**
     * @param string|StatementContainerInterface $target
     * @throws InvalidArgumentException
     * @return $this Provides a fluent interface
     */
    #[\Override]
    public function profilerStart(StatementContainerInterface|string $target): static
    {
        $profileInformation = [
            'parameters' => null,
            'start'      => microtime(true),
            'end'        => null,
            'elapse'     => null,
        ];
        if ($target instanceof StatementContainerInterface) {
            $profileInformation['sql']        = $target->getSql();
            $profileInformation['parameters'] = clone $target->getParameterContainer();
        } else {
            $profileInformation['sql'] = $target;
        }

        $this->profiles[$this->currentIndex] = $profileInformation;

        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    #[\Override]
    public function profilerFinish(): static
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
     * @return array|null
     */
    public function getLastProfile(): ?array
    {
        return end($this->profiles);
    }

    /**
     * @return array
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }
}
