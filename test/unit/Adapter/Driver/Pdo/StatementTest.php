<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Exception\InvalidQueryException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\SqliteMemoryPdo;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestConnection;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdo;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function sprintf;

#[CoversMethod(Statement::class, 'setDriver')]
#[CoversMethod(Statement::class, 'setParameterContainer')]
#[CoversMethod(Statement::class, 'getParameterContainer')]
#[CoversMethod(Statement::class, 'getResource')]
#[CoversMethod(Statement::class, 'setSql')]
#[CoversMethod(Statement::class, 'getSql')]
#[CoversMethod(Statement::class, 'prepare')]
#[CoversMethod(Statement::class, 'isPrepared')]
#[CoversMethod(Statement::class, 'execute')]
#[CoversMethod(Statement::class, 'bindParametersFromContainer')]
#[CoversMethod(Statement::class, 'setProfiler')]
#[CoversMethod(Statement::class, 'getProfiler')]
#[CoversMethod(Statement::class, 'initialize')]
#[CoversMethod(Statement::class, 'setResource')]
#[CoversMethod(Statement::class, '__clone')]
#[CoversMethod(Statement::class, '__construct')]
#[Group('unit')]
final class StatementTest extends TestCase
{
    protected Statement $statement;

    /** @return array<string, array{string}> */
    public static function invalidParameterNameProvider(): array
    {
        return [
            'dollar sign' => ['tz$'],
            'with colon'  => [':tz$'],
            'hyphen'      => ['my-param'],
            'space'       => ['my param'],
            'dot'         => ['my.param'],
            'at sign'     => ['param@name'],
        ];
    }

    #[Test]
    public function bindParametersDetectsBooleanValueType(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', true);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function bindParametersDetectsNullValueType(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', null);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function bindParametersFromContainerSkipsWhenAlreadyBound(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');
        $this->statement->setParameterContainer(new ParameterContainer(['val' => 'first']));

        $result1 = $this->statement->execute();
        static::assertInstanceOf(Result::class, $result1);
    }

    #[Test]
    public function bindParametersWithErrataTypeDefaultsToString(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', 'data', ParameterContainer::TYPE_BINARY);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function bindParametersWithErrataTypeInteger(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', 42, ParameterContainer::TYPE_INTEGER);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function bindParametersWithErrataTypeLob(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', 'data', ParameterContainer::TYPE_LOB);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function bindParametersWithErrataTypeNull(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', null, ParameterContainer::TYPE_NULL);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function bindParametersWithPositionalIntegers(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT ?, ?, ?');

        $container = new ParameterContainer();
        $container->offsetSet(0, 'a');
        $container->offsetSet(1, 'b');
        $container->offsetSet(2, 'c');
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function cloneClonesParameterContainerWhenSet(): void
    {
        $container = new ParameterContainer(['key' => 'value']);
        $statement = new Statement($container);

        $clone = clone $statement;

        static::assertNotSame($container, $clone->getParameterContainer());
        static::assertSame('value', $clone->getParameterContainer()->offsetGet('key'));
    }

    #[Test]
    public function cloneResetsState(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();

        $clone = clone $this->statement;

        static::assertFalse($clone->isPrepared());
        static::assertNull($clone->getResource());
        static::assertNotSame(
            $this->statement->getParameterContainer(),
            $clone->getParameterContainer(),
        );
    }

    #[Test]
    public function constructorAcceptsParameterContainerAndOptions(): void
    {
        $container = new ParameterContainer(['key' => 'value']);
        $statement = new Statement($container, ['option' => true]);

        static::assertSame($container, $statement->getParameterContainer());
    }

    #[Test]
    public function executeAutoPrepares(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');

        static::assertFalse($this->statement->isPrepared());

        $result = $this->statement->execute();

        static::assertInstanceOf(Result::class, $result);
        static::assertTrue($this->statement->isPrepared());
    }

    #[Test]
    public function executeCallsProfilerFinishOnFailure(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);
        $profiler->expects($this->once())->method('profilerStart')->willReturnSelf();
        $profiler->expects($this->once())->method('profilerFinish')->willReturnSelf();

        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willThrowException(new PDOException('fail'));
        $pdoStmt->method('errorInfo')->willReturn(['HY000', 1, 'fail']);

        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();
        $this->statement->setProfiler($profiler);

        $reflection = new ReflectionProperty($this->statement, 'resource');
        $reflection->setValue($this->statement, $pdoStmt);

        self::expectException(InvalidQueryException::class);
        $this->statement->execute();
    }

    #[Test]
    public function executeCallsProfilerOnSuccess(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);
        $profiler->expects($this->once())->method('profilerStart')->willReturnSelf();
        $profiler->expects($this->once())->method('profilerFinish')->willReturnSelf();

        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->setProfiler($profiler);

        $this->statement->execute();
    }

    #[Test]
    public function executeCastsNonIntErrorCodeToZero(): void
    {
        $pdoException = new PDOException('fail');
        $ref          = new ReflectionProperty($pdoException, 'code');
        $ref->setValue($pdoException, 'HY000');

        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willThrowException($pdoException);
        $pdoStmt->method('errorInfo')->willReturn(['HY000', 1, 'fail']);

        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();

        $reflection = new ReflectionProperty($this->statement, 'resource');
        $reflection->setValue($this->statement, $pdoStmt);

        try {
            $this->statement->execute();
            static::fail('Expected InvalidQueryException');
        } catch (InvalidQueryException $e) {
            static::assertSame(0, $e->getCode());
        }
    }

    #[Test]
    public function executeReturnsResult(): void
    {
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo = new SqliteMemoryPdo())));
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');
        static::assertInstanceOf(Result::class, $this->statement->execute());
    }

    #[Test]
    public function executeThrowsInvalidQueryExceptionOnPdoException(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willThrowException(new PDOException('execute failed'));
        $pdoStmt->method('errorInfo')->willReturn(['HY000', 1, 'execute failed']);

        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();

        $reflection = new ReflectionProperty($this->statement, 'resource');
        $reflection->setValue($this->statement, $pdoStmt);

        self::expectException(InvalidQueryException::class);
        $this->statement->execute();
    }

    #[Test]
    #[DataProvider('invalidParameterNameProvider')]
    public function executeThrowsOnInvalidParameterName(string $name): void
    {
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo = new SqliteMemoryPdo())));
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::INVALID_PDO_PARAM, $name));
        $this->statement->execute([$name => 'value']);
    }

    #[Test]
    public function executeWithArrayParametersMergesIntoContainer(): void
    {
        $pdo       = new SqliteMemoryPdo();
        $statement = new Statement();
        $statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $statement->initialize($pdo);
        $statement->setSql('SELECT :name');

        $result = $statement->execute(['name' => 'test']);

        static::assertInstanceOf(Result::class, $result);
    }

    #[Test]
    public function executeWithParameterContainerSetsContainer(): void
    {
        $pdo       = new SqliteMemoryPdo();
        $statement = new Statement();
        $statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $statement->initialize($pdo);
        $statement->setSql('SELECT ?');

        $container = new ParameterContainer();
        $container->offsetSet(null, 'value');

        $result = $statement->execute($container);

        static::assertInstanceOf(Result::class, $result);
        static::assertSame($container, $statement->getParameterContainer());
    }

    #[Test]
    public function fluentPrepare(): void
    {
        $this->statement->initialize(new SqliteMemoryPdo());
        $result = $this->statement->prepare('SELECT 1');
        static::assertInstanceOf(Statement::class, $result);
        static::assertSame($this->statement, $result);
    }

    #[Test]
    public function fluentSetDriver(): void
    {
        static::assertEquals($this->statement, $this->statement->setDriver(new TestPdo([])));
    }

    #[Test]
    public function fluentSetParameterContainer(): void
    {
        static::assertSame($this->statement, $this->statement->setParameterContainer(new ParameterContainer()));
    }

    #[Test]
    public function getParameterContainerReturnsContainer(): void
    {
        $container = new ParameterContainer();
        $this->statement->setParameterContainer($container);
        static::assertSame($container, $this->statement->getParameterContainer());
    }

    #[Test]
    public function getProfilerReturnsNullByDefault(): void
    {
        static::assertNull($this->statement->getProfiler());
    }

    #[Test]
    public function getResourceReturnsPdoStatement(): void
    {
        $pdo  = new SqliteMemoryPdo();
        $stmt = $pdo->prepare('SELECT 1');
        $this->statement->setResource($stmt);

        static::assertSame($stmt, $this->statement->getResource());
    }

    #[Test]
    public function getSql(): void
    {
        $this->statement->setSql('SELECT 1');
        static::assertSame('SELECT 1', $this->statement->getSql());
    }

    #[Test]
    public function initializeSetsPdoResource(): void
    {
        $pdo = new SqliteMemoryPdo();

        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();

        static::assertTrue($this->statement->isPrepared());
    }

    #[Test]
    public function isPreparedReturnsTrueAfterPrepare(): void
    {
        static::assertFalse($this->statement->isPrepared());
        $this->statement->initialize(new SqliteMemoryPdo());
        $this->statement->prepare('SELECT 1');
        static::assertTrue($this->statement->isPrepared());
    }

    #[Test]
    public function prepareThrowsRuntimeExceptionOnPdoFailure(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn(false);
        $pdo->method('errorInfo')->willReturn(['HY000', 1, 'Prepare failed']);

        $this->statement->initialize($pdo);
        $this->statement->setSql('INVALID SQL');

        self::expectException(RuntimeException::class);
        $this->statement->prepare();
    }

    #[Test]
    public function prepareThrowsWhenAlreadyPrepared(): void
    {
        $this->statement->initialize(new SqliteMemoryPdo());
        $this->statement->prepare('SELECT 1');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::ALREADY_PREPARED);

        $this->statement->prepare('SELECT 2');
    }

    #[Test]
    public function secondExecuteSkipsBindingWhenAlreadyBound(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');
        $this->statement->setParameterContainer(new ParameterContainer(['val' => 'test']));

        $result1 = $this->statement->execute();
        static::assertInstanceOf(Result::class, $result1);

        $result2 = $this->statement->execute();
        static::assertInstanceOf(Result::class, $result2);
    }

    #[Test]
    public function setProfilerStoresProfiler(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);

        $this->statement->setProfiler($profiler);

        static::assertSame($profiler, $this->statement->getProfiler());
    }

    #[Test]
    public function setResourceStoresPdoStatement(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);

        $this->statement->setResource($pdoStmt);

        static::assertSame($pdoStmt, $this->statement->getResource());
    }

    #[Test]
    public function setSql(): void
    {
        $this->statement->setSql('SELECT 1');
        static::assertSame('SELECT 1', $this->statement->getSql());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->statement = new Statement();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {}
}
