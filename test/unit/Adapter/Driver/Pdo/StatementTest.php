<?php

namespace LaminasTest\Db\Adapter\Driver\Pdo;

use Laminas\Db\Adapter\Driver\Pdo\Connection;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\ParameterContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
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
final class StatementTest extends TestCase
{
    protected Statement $statement;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
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
        self::assertEquals($this->statement, $this->statement->setDriver(new Pdo([])));
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
     * @todo Implement testPrepare().
     */
    public function testPrepare(): void
    {
        $this->statement->initialize(new TestAsset\SqliteMemoryPdo());
        self::assertNull($this->statement->prepare('SELECT 1'));
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
        $this->statement->setDriver(new Pdo(new Connection($pdo = new TestAsset\SqliteMemoryPdo())));
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');
        self::assertInstanceOf(Result::class, $this->statement->execute());
    }
}
