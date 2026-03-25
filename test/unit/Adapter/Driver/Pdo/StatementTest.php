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
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

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
    protected function tearDown(): void
    {
    }

    public function testSetDriver(): void
    {
        self::assertEquals($this->statement, $this->statement->setDriver(new TestPdo([])));
    }

    public function testSetParameterContainer(): void
    {
        self::assertSame($this->statement, $this->statement->setParameterContainer(new ParameterContainer()));
    }

    /**
     * @todo Implement testGetParameterContainer().
     */
    public function testGetParameterContainer(): void
    {
        $container = new ParameterContainer();
        $this->statement->setParameterContainer($container);
        self::assertSame($container, $this->statement->getParameterContainer());
    }

    public function testGetResource(): void
    {
        $pdo  = new SqliteMemoryPdo();
        $stmt = $pdo->prepare('SELECT 1');
        $this->statement->setResource($stmt);

        self::assertSame($stmt, $this->statement->getResource());
    }

    public function testSetSql(): void
    {
        $this->statement->setSql('SELECT 1');
        self::assertEquals('SELECT 1', $this->statement->getSql());
    }

    public function testGetSql(): void
    {
        $this->statement->setSql('SELECT 1');
        self::assertEquals('SELECT 1', $this->statement->getSql());
    }

    /**
     * Test that prepare() returns the statement for method chaining
     */
    public function testPrepare(): void
    {
        $this->statement->initialize(new SqliteMemoryPdo());
        $result = $this->statement->prepare('SELECT 1');
        self::assertInstanceOf(Statement::class, $result);
        self::assertSame($this->statement, $result);
    }

    public function testIsPrepared(): void
    {
        self::assertFalse($this->statement->isPrepared());
        $this->statement->initialize(new SqliteMemoryPdo());
        $this->statement->prepare('SELECT 1');
        self::assertTrue($this->statement->isPrepared());
    }

    public function testExecute(): void
    {
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo = new SqliteMemoryPdo())));
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');
        self::assertInstanceOf(Result::class, $this->statement->execute());
    }

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

    #[DataProvider('invalidParameterNameProvider')]
    public function testExecuteThrowsOnInvalidParameterName(string $name): void
    {
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo = new SqliteMemoryPdo())));
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('contains invalid characters');
        $this->statement->execute([$name => 'value']);
    }

    public function testSetProfilerStoresProfiler(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);

        $this->statement->setProfiler($profiler);

        self::assertSame($profiler, $this->statement->getProfiler());
    }

    public function testGetProfilerReturnsNullByDefault(): void
    {
        self::assertNull($this->statement->getProfiler());
    }

    public function testInitializeSetsPdoResource(): void
    {
        $pdo = new SqliteMemoryPdo();

        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();

        self::assertTrue($this->statement->isPrepared());
    }

    public function testSetResourceStoresPdoStatement(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);

        $this->statement->setResource($pdoStmt);

        self::assertSame($pdoStmt, $this->statement->getResource());
    }

    public function testPrepareThrowsRuntimeExceptionOnPdoFailure(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn(false);
        $pdo->method('errorInfo')->willReturn(['HY000', 1, 'Prepare failed']);

        $this->statement->initialize($pdo);
        $this->statement->setSql('INVALID SQL');

        $this->expectException(RuntimeException::class);
        $this->statement->prepare();
    }

    public function testExecuteThrowsInvalidQueryExceptionOnPdoException(): void
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

        $this->expectException(InvalidQueryException::class);
        $this->statement->execute();
    }

    public function testExecuteCallsProfilerOnSuccess(): void
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

    public function testExecuteCallsProfilerFinishOnFailure(): void
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

        $this->expectException(InvalidQueryException::class);
        $this->statement->execute();
    }

    public function testCloneResetsState(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');
        $this->statement->prepare();

        $clone = clone $this->statement;

        self::assertFalse($clone->isPrepared());
        self::assertNull($clone->getResource());
        self::assertNotSame(
            $this->statement->getParameterContainer(),
            $clone->getParameterContainer()
        );
    }

    public function testBindParametersWithPositionalIntegers(): void
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

        self::assertInstanceOf(Result::class, $result);
    }

    public function testConstructorAcceptsParameterContainerAndOptions(): void
    {
        $container = new ParameterContainer(['key' => 'value']);
        $statement = new Statement($container, ['option' => true]);

        self::assertSame($container, $statement->getParameterContainer());
    }

    public function testPrepareThrowsWhenAlreadyPrepared(): void
    {
        $this->statement->initialize(new SqliteMemoryPdo());
        $this->statement->prepare('SELECT 1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This statement has been prepared already');

        $this->statement->prepare('SELECT 2');
    }

    public function testExecuteWithParameterContainerSetsContainer(): void
    {
        $pdo       = new SqliteMemoryPdo();
        $statement = new Statement();
        $statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $statement->initialize($pdo);
        $statement->setSql('SELECT ?');

        $container = new ParameterContainer();
        $container->offsetSet(null, 'value');

        $result = $statement->execute($container);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame($container, $statement->getParameterContainer());
    }

    public function testExecuteWithArrayParametersMergesIntoContainer(): void
    {
        $pdo       = new SqliteMemoryPdo();
        $statement = new Statement();
        $statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $statement->initialize($pdo);
        $statement->setSql('SELECT :name');

        $result = $statement->execute(['name' => 'test']);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testExecuteCastsNonIntErrorCodeToZero(): void
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
            self::fail('Expected InvalidQueryException');
        } catch (InvalidQueryException $e) {
            self::assertSame(0, $e->getCode());
        }
    }

    public function testBindParametersFromContainerSkipsWhenAlreadyBound(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');
        $this->statement->setParameterContainer(new ParameterContainer(['val' => 'first']));

        $result1 = $this->statement->execute();
        self::assertInstanceOf(Result::class, $result1);
    }

    public function testBindParametersWithErrataTypeInteger(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', 42, ParameterContainer::TYPE_INTEGER);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
    }

    public function testBindParametersWithErrataTypeNull(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', null, ParameterContainer::TYPE_NULL);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
    }

    public function testBindParametersWithErrataTypeLob(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', 'data', ParameterContainer::TYPE_LOB);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
    }

    public function testBindParametersWithErrataTypeDefaultsToString(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', 'data', ParameterContainer::TYPE_BINARY);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
    }

    public function testBindParametersDetectsNullValueType(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', null);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
    }

    public function testBindParametersDetectsBooleanValueType(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT :val');

        $container = new ParameterContainer();
        $container->offsetSet('val', true);
        $this->statement->setParameterContainer($container);

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
    }

    public function testCloneWithNullParameterContainerDoesNotClone(): void
    {
        $statement = new Statement(null);
        $clone     = clone $statement;

        self::assertFalse($clone->isPrepared());
        self::assertNull($clone->getResource());
    }

    public function testExecuteAutoPrepares(): void
    {
        $pdo = new SqliteMemoryPdo();
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo)));
        $this->statement->initialize($pdo);
        $this->statement->setSql('SELECT 1');

        self::assertFalse($this->statement->isPrepared());

        $result = $this->statement->execute();

        self::assertInstanceOf(Result::class, $result);
        self::assertTrue($this->statement->isPrepared());
    }
}
