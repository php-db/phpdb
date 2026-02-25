<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use Override;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\ParameterContainer;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestConnection;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdo;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
        $pdo  = new TestAsset\SqliteMemoryPdo();
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
        $this->statement->initialize(new TestAsset\SqliteMemoryPdo());
        $result = $this->statement->prepare('SELECT 1');
        self::assertInstanceOf(Statement::class, $result);
        self::assertSame($this->statement, $result);
    }

    public function testIsPrepared(): void
    {
        self::assertFalse($this->statement->isPrepared());
        $this->statement->initialize(new TestAsset\SqliteMemoryPdo());
        $this->statement->prepare('SELECT 1');
        self::assertTrue($this->statement->isPrepared());
    }

    public function testExecute(): void
    {
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo = new TestAsset\SqliteMemoryPdo())));
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
        $this->statement->setDriver(new TestPdo(new TestConnection($pdo = new TestAsset\SqliteMemoryPdo())));
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('contains invalid characters');
        $this->statement->execute([$name => 'value']);
    }
}
