<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\StatementContainerInterface;

interface ProfilerInterface
{
    /**
     * @param string|StatementContainerInterface $target
     * @return mixed
     */
    public function profilerStart($target);

    /**
     * @return $this
     */
    public function profilerFinish();
}
