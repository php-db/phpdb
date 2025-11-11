<?php

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\StatementContainerInterface;

interface ProfilerInterface
{
    /**
     * @param string|StatementContainerInterface $target
     * @return mixed
     */
    public function profilerStart(string|StatementContainerInterface $target);

    public function profilerFinish();
}
