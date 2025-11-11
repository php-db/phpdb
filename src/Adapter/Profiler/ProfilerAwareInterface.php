<?php

namespace PhpDb\Adapter\Profiler;

interface ProfilerAwareInterface
{
    public function setProfiler(ProfilerInterface $profiler);
}
