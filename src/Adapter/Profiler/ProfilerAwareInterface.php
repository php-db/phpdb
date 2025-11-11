<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Profiler;

interface ProfilerAwareInterface
{
    /** Implementation should provide a fluent interface */
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface;
}
