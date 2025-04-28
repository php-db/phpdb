<?php

namespace LaminasTest\Db\Adapter\Profiler;

use Laminas\Db\Adapter\Exception\InvalidArgumentException;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\Adapter\StatementContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Profiler::class, 'profilerStart')]
#[CoversMethod(Profiler::class, 'profilerFinish')]
#[CoversMethod(Profiler::class, 'getLastProfile')]
#[CoversMethod(Profiler::class, 'getProfiles')]
class ProfilerTest extends TestCase
{
    protected Profiler $profiler;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->profiler = new Profiler();
    }

    public function testProfilerStart(): void
    {
        $ret = $this->profiler->profilerStart('SELECT * FROM FOO');
        self::assertSame($this->profiler, $ret);
        $ret = $this->profiler->profilerStart(new StatementContainer());
        self::assertSame($this->profiler, $ret);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('profilerStart takes either a StatementContainer or a string');
        $this->profiler->profilerStart(5);
    }

    public function testProfilerFinish(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO');
        $ret = $this->profiler->profilerFinish();
        self::assertSame($this->profiler, $ret);

        $profiler = new Profiler();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A profile must be started before profilerFinish can be called');
        $profiler->profilerFinish();
    }

    public function testGetLastProfile(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO');
        $this->profiler->profilerFinish();
        $profile = $this->profiler->getLastProfile();
        self::assertEquals('SELECT * FROM FOO', $profile['sql']);
        self::assertNull($profile['parameters']);
        self::assertIsFloat($profile['start']);
        self::assertIsFloat($profile['end']);
        self::assertIsFloat($profile['elapse']);
    }

    public function testGetProfiles(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO1');
        $this->profiler->profilerFinish();
        $this->profiler->profilerStart('SELECT * FROM FOO2');
        $this->profiler->profilerFinish();

        self::assertCount(2, $this->profiler->getProfiles());
    }
}
