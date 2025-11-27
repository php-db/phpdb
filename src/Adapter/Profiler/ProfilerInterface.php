<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\StatementContainerInterface;

interface ProfilerInterface
{
    public function profilerStart(string|StatementContainerInterface $target): ProfilerInterface;

    /**
     * @return $this
     */
    public function profilerFinish(): ProfilerInterface;
}
