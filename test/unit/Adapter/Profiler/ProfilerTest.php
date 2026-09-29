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

    #[\PHPUnit\Framework\Attributes\Test]
    public function getLastProfileReturnsSqlAndTimings(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO');
        $this->profiler->profilerFinish();
        $profile = $this->profiler->getLastProfile();
        static::assertSame('SELECT * FROM FOO', $profile['sql']);
        static::assertNull($profile['parameters']);
        static::assertIsFloat($profile['start']);
        static::assertIsFloat($profile['end']);
        static::assertIsFloat($profile['elapse']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProfilesReturnsAllRecordedProfiles(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO1');
        $this->profiler->profilerFinish();
        $this->profiler->profilerStart('SELECT * FROM FOO2');
        $this->profiler->profilerFinish();

        static::assertCount(2, $this->profiler->getProfiles());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profilerFinishThrowsWithoutStart(): void
    {
        $this->profiler->profilerStart('SELECT * FROM FOO');
        $ret = $this->profiler->profilerFinish();
        static::assertSame($this->profiler, $ret);

        $profiler = new Profiler();
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNSTARTED_PROFILE, 'profilerFinish'));
        $profiler->profilerFinish();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profilerStartClonesParameterContainerFromStatementContainer(): void
    {
        $parameterContainer = new ParameterContainer(['key' => 'value']);
        $statementContainer = new StatementContainer('SELECT ?', $parameterContainer);

        $this->profiler->profilerStart($statementContainer);
        $this->profiler->profilerFinish();

        $profile = $this->profiler->getLastProfile();

        static::assertSame('SELECT ?', $profile['sql']);
        static::assertInstanceOf(ParameterContainer::class, $profile['parameters']);
        static::assertNotSame($parameterContainer, $profile['parameters']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profilerStartWithStatementContainer(): void
    {
        $ret = $this->profiler->profilerStart(new StatementContainer());
        static::assertSame($this->profiler, $ret);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profilerStartWithString(): void
    {
        $ret = $this->profiler->profilerStart('SELECT * FROM FOO');
        static::assertSame($this->profiler, $ret);
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
