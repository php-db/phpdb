<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

use PhpDb\Adapter\StatementContainerInterface;

/**
 * @api
 */
interface ProfilerInterface
{
    /**
     * @return $this
     */
    public function profilerFinish(): self;

    public function profilerStart(string|StatementContainerInterface $target): self;
}
