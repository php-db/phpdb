<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Profiler;

use Override;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Profiler\Profiler;
use PhpDb\Adapter\StatementContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(Profiler::class, 'profilerStart')]
#[CoversMethod(Profiler::class, 'profilerFinish')]
#[CoversMethod(Profiler::class, 'getLastProfile')]
#[CoversMethod(Profiler::class, 'getProfiles')]
#[Group('unit')]
final class ProfilerTest extends TestCase
{
    protected Profiler $profiler;

    public function testGetLastProfileReturnsSqlAndTimings(): void
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

    public function testGetProfilesReturnsAllRecordedProfiles(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO1');
        $this->profiler->profilerFinish();
        $this->profiler->profilerStart('SELECT * FROM FOO2');
        $this->profiler->profilerFinish();

        self::assertCount(2, $this->profiler->getProfiles());
    }

    public function testProfilerFinishThrowsWithoutStart(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO');
        $ret = $this->profiler->profilerFinish();
        self::assertSame($this->profiler, $ret);

        $profiler = new Profiler();
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNSTARTED_PROFILE, 'profilerFinish'));
        $profiler->profilerFinish();
    }

    public function testProfilerStartClonesParameterContainerFromStatementContainer(): void
    {
        $parameterContainer = new ParameterContainer(['key' => 'value']);
        $statementContainer = new StatementContainer('SELECT ?', $parameterContainer);

        $this->profiler->profilerStart($statementContainer);
        $this->profiler->profilerFinish();

        $profile = $this->profiler->getLastProfile();

        self::assertSame('SELECT ?', $profile['sql']);
        self::assertInstanceOf(ParameterContainer::class, $profile['parameters']);
        self::assertNotSame($parameterContainer, $profile['parameters']);
    }

    public function testProfilerStartWithStatementContainer(): void
    {
        $ret = $this->profiler->profilerStart(new StatementContainer());
        self::assertSame($this->profiler, $ret);
    }

    public function testProfilerStartWithString(): void
    {
        $ret = $this->profiler->profilerStart('SELECT * FROM FOO');
        self::assertSame($this->profiler, $ret);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->profiler = new Profiler();
    }
}
