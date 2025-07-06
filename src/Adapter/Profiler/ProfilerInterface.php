<?php

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\StatementContainerInterface;

interface ProfilerInterface
{
    /**
     * @param string|StatementContainerInterface $target
     * @return mixed
     */
    public function profilerStart($target);

    public function profilerFinish();
}
