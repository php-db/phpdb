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
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPdo::class, 'getResultPrototype')]
#[CoversMethod(AbstractPdo::class, '__construct')]
#[CoversMethod(AbstractPdo::class, 'checkEnvironment')]
#[CoversMethod(AbstractPdo::class, 'getConnection')]
#[CoversMethod(AbstractPdo::class, 'createStatement')]
#[CoversMethod(AbstractPdo::class, 'getPrepareType')]
#[CoversMethod(AbstractPdo::class, 'getLastGeneratedValue')]
#[CoversMethod(AbstractPdo::class, 'setProfiler')]
#[CoversMethod(AbstractPdo::class, 'getProfiler')]
#[Group('unit')]
final class PdoTest extends TestCase
{
    protected TestPdo $pdo;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->pdo = new TestPdo([]);
    }

    /** @psalm-return array<array-key, array{0: int|string, 1: null|string, 2: string}> */
    public static function getParamsAndType(): array
    {
        return [
            ['foo', null, ':foo'],
            ['foo_bar', null, ':foo_bar'],
            ['123foo', null, ':123foo'],
            [1, null, '?'],
            ['1', null, '?'],
            ['foo', DriverInterface::PARAMETERIZATION_NAMED, ':foo'],
            ['foo_bar', DriverInterface::PARAMETERIZATION_NAMED, ':foo_bar'],
            ['123foo', DriverInterface::PARAMETERIZATION_NAMED, ':123foo'],
            [1, DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            ['1', DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            [':foo', null, ':foo'],
        ];
    }

    #[DataProvider('getParamsAndType')]
    public function testFormatParameterNameFormatsCorrectly(int|string $name, ?string $type, string $expected): void
    {
        $result = $this->pdo->formatParameterName($name, $type);
        $this->assertEquals($expected, $result);
    }

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

    #[DataProvider('getInvalidParamName')]
    public function testFormatParameterNameWithInvalidCharacters(string $name): void
    {
        $this->expectException(RuntimeException::class);
        $this->pdo->formatParameterName($name);
    }

    public function testGetResultPrototypeReturnsResult(): void
    {
        $resultPrototype = $this->pdo->getResultPrototype();

        self::assertInstanceOf(Result::class, $resultPrototype);
    }

    public function testConstructorSetsDriverOnConnection(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        self::assertSame($connection, $pdo->getConnection());
    }

    public function testCheckEnvironmentReturnsTrue(): void
    {
        self::assertTrue($this->pdo->checkEnvironment());
    }

    public function testGetConnectionReturnsConnectionInstance(): void
    {
        $connection = $this->pdo->getConnection();

        self::assertInstanceOf(TestConnection::class, $connection);
    }

    public function testCreateStatementWithSqlString(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        $statement = $pdo->createStatement('SELECT 1');

        self::assertInstanceOf(Statement::class, $statement);
        self::assertSame('SELECT 1', $statement->getSql());
    }

    public function testCreateStatementWithNullConnectsAndInitializes(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $pdo        = new TestPdo($connection);

        $statement = $pdo->createStatement();

        self::assertInstanceOf(Statement::class, $statement);
    }

    public function testGetPrepareTypeReturnsNamed(): void
    {
        self::assertSame(DriverInterface::PARAMETERIZATION_NAMED, $this->pdo->getPrepareType());
    }

    public function testGetLastGeneratedValueDelegatesToConnection(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        $value = $pdo->getLastGeneratedValue();

        self::assertSame('0', $value);
    }

    public function testSetProfilerPropagatesProfilerToConnectionAndStatement(): void
    {
        $profiler   = $this->createMock(ProfilerInterface::class);
        $connection = new TestConnection(new SqliteMemoryPdo());
        $statement  = new Statement();
        $pdo        = new TestPdo($connection, $statement);

        $pdo->setProfiler($profiler);

        self::assertSame($profiler, $pdo->getProfiler());
        self::assertSame($profiler, $connection->getProfiler());
        self::assertSame($profiler, $statement->getProfiler());
    }

    public function testGetProfilerThrowsWhenNotInitialized(): void
    {
        $pdo = new TestPdo([]);

        $this->expectException(Error::class);

        $unused = $pdo->getProfiler();
    }

    public function testGetProfilerReturnsSetProfiler(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);

        $this->pdo->setProfiler($profiler);

        self::assertSame($profiler, $this->pdo->getProfiler());
    }

    public function testConstructorAddsFeaturesWhenDriverSupportsFeatures(): void
    {
        $feature    = $this->createMock(DriverFeatureInterface::class);
        $connection = new TestConnection(new SqliteMemoryPdo());

        $pdo = new TestPdoWithFeatures($connection, features: [$feature]);

        self::assertSame($feature, $pdo->getFeature($feature::class));
    }

    public function testCreateStatementWithPdoStatementResource(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $pdo        = new TestPdo($connection);

        $pdoStmt   = $this->createMock(PDOStatement::class);
        $statement = $pdo->createStatement($pdoStmt);

        self::assertInstanceOf(Statement::class, $statement);
        self::assertSame($pdoStmt, $statement->getResource());
    }

    public function testFormatParameterNameReturnsQuestionMarkForNumericWithoutType(): void
    {
        self::assertSame('?', $this->pdo->formatParameterName(42));
    }
}
