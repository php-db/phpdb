<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use Error;
use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Exception\RuntimeException;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\SqliteMemoryPdo;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestConnection;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdo;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdoWithFeatures;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPdo::class, 'getResultPrototype')]
#[CoversMethod(AbstractPdo::class, 'checkEnvironment')]
#[CoversMethod(AbstractPdo::class, 'getConnection')]
#[CoversMethod(AbstractPdo::class, 'createStatement')]
#[CoversMethod(AbstractPdo::class, 'getPrepareType')]
#[CoversMethod(AbstractPdo::class, 'getLastGeneratedValue')]
#[CoversMethod(AbstractPdo::class, 'setProfiler')]
#[CoversMethod(AbstractPdo::class, 'getProfiler')]
#[CoversMethod(AbstractPdo::class, 'formatParameterName')]
#[Group('unit')]
final class PdoTest extends TestCase
{
    protected TestPdo $pdo;

    /** @psalm-return array<array-key, array{0: string}> */
    public static function getInvalidParamName(): array
    {
        return [
            ['foo%'],
            ['foo-'],
            ['foo$'],
            ['foo0!'],
        ];
    }

    /** @psalm-return array<array-key, array{0: int|string, 1: null|string, 2: string}> */
    public static function getParamsAndType(): array
    {
        return [
            ['foo',     null,                                    ':foo'],
            ['foo_bar', null,                                    ':foo_bar'],
            ['123foo',  null,                                    ':123foo'],
            [1,         null,                                    '?'],
            ['1',       null,                                    '?'],
            ['foo',     DriverInterface::PARAMETERIZATION_NAMED, ':foo'],
            ['foo_bar', DriverInterface::PARAMETERIZATION_NAMED, ':foo_bar'],
            ['123foo',  DriverInterface::PARAMETERIZATION_NAMED, ':123foo'],
            [1,         DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            ['1',       DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            [':foo',    null,                                    ':foo'],
        ];
    }

    #[Test]
    public function checkEnvironmentReturnsTrue(): void
    {
        static::assertTrue($this->pdo->checkEnvironment());
    }

    #[Test]
    public function constructorAddsFeaturesWhenDriverSupportsFeatures(): void
    {
        $feature    = $this->createMock(DriverFeatureInterface::class);
        $connection = new TestConnection(new SqliteMemoryPdo());

        $pdo = new TestPdoWithFeatures($connection, features: [$feature]);

        static::assertSame($feature, $pdo->getFeature($feature::class));
    }

    #[Test]
    public function constructorSetsDriverOnConnection(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        static::assertSame($connection, $pdo->getConnection());
    }

    #[Test]
    public function createStatementWithNullConnectsAndInitializes(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $pdo        = new TestPdo($connection);

        $statement = $pdo->createStatement();

        static::assertInstanceOf(Statement::class, $statement);
    }

    #[Test]
    public function createStatementWithPdoStatementResource(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        $pdoStmt   = $this->createMock(PDOStatement::class);
        $statement = $pdo->createStatement($pdoStmt);

        static::assertInstanceOf(Statement::class, $statement);
        static::assertSame($pdoStmt, $statement->getResource());
    }

    #[Test]
    public function createStatementWithSqlString(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        $statement = $pdo->createStatement('SELECT 1');

        static::assertInstanceOf(Statement::class, $statement);
        static::assertSame('SELECT 1', $statement->getSql());
    }

    #[Test]
    #[DataProvider('getParamsAndType')]
    public function formatParameterNameFormatsCorrectly(int|string $name, ?string $type, string $expected): void
    {
        $result = $this->pdo->formatParameterName($name, $type);
        static::assertEquals($expected, $result);
    }

    #[Test]
    public function formatParameterNameReturnsQuestionMarkForNumericWithoutType(): void
    {
        static::assertSame('?', $this->pdo->formatParameterName(42));
    }

    #[Test]
    #[DataProvider('getInvalidParamName')]
    public function formatParameterNameWithInvalidCharacters(string $name): void
    {
        $this->expectException(RuntimeException::class);
        $this->pdo->formatParameterName($name);
    }

    #[Test]
    public function getConnectionReturnsConnectionInstance(): void
    {
        $connection = $this->pdo->getConnection();

        static::assertInstanceOf(TestConnection::class, $connection);
    }

    #[Test]
    public function getLastGeneratedValueDelegatesToConnection(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        $value = $pdo->getLastGeneratedValue();

        static::assertSame('0', $value);
    }

    #[Test]
    public function getPrepareTypeReturnsNamed(): void
    {
        static::assertSame(DriverInterface::PARAMETERIZATION_NAMED, $this->pdo->getPrepareType());
    }

    #[Test]
    public function getProfilerReturnsSetProfiler(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);

        $this->pdo->setProfiler($profiler);

        static::assertSame($profiler, $this->pdo->getProfiler());
    }

    #[Test]
    public function getProfilerThrowsWhenNotInitialized(): void
    {
        $pdo = new TestPdo([]);

        $this->expectException(Error::class);

        $unused = $pdo->getProfiler();
    }

    #[Test]
    public function getResultPrototypeReturnsResult(): void
    {
        $resultPrototype = $this->pdo->getResultPrototype();

        static::assertInstanceOf(Result::class, $resultPrototype);
    }

    #[Test]
    public function setProfilerPropagatesProfilerToConnectionAndStatement(): void
    {
        $profiler   = $this->createMock(ProfilerInterface::class);
        $connection = new TestConnection(new SqliteMemoryPdo());
        $statement  = new Statement();
        $pdo        = new TestPdo($connection, $statement);

        $pdo->setProfiler($profiler);

        static::assertSame($profiler, $pdo->getProfiler());
        static::assertSame($profiler, $connection->getProfiler());
        static::assertSame($profiler, $statement->getProfiler());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->pdo = new TestPdo([]);
    }
}
