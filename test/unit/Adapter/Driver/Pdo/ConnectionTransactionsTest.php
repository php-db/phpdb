<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use Override;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDbTest\TestAsset\ConnectionWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see AbstractPdoConnection} transaction support
 */
#[CoversClass(AbstractPdoConnection::class)]
#[CoversClass(AbstractConnection::class)]
#[CoversMethod(AbstractPdoConnection::class, 'beginTransaction')]
#[CoversMethod(AbstractConnection::class, 'inTransaction')]
#[CoversMethod(AbstractPdoConnection::class, 'commit')]
#[CoversMethod(AbstractPdoConnection::class, 'rollback')]
final class ConnectionTransactionsTest extends TestCase
{
    protected ConnectionWrapper $wrapper;

    #[Test]
    public function beginTransactionReturnsInstanceOfConnection(): void
    {
        static::assertInstanceOf(ConnectionInterface::class, $this->wrapper->beginTransaction());
    }

    #[Test]
    public function beginTransactionSetsInTransactionAtTrue(): void
    {
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
    }

    #[Test]
    public function commitReturnsInstanceOfConnection(): void
    {
        $this->wrapper->beginTransaction();
        static::assertInstanceOf(ConnectionInterface::class, $this->wrapper->commit());
    }

    #[Test]
    public function commitSetsInTransactionAtFalse(): void
    {
        $this->wrapper->beginTransaction();
        $this->wrapper->commit();
        static::assertFalse($this->wrapper->inTransaction());
    }

    /**
     * Standalone commit after a SET autocommit=0;
     */
    #[Test]
    public function commitWithoutBeginReturnsInstanceOfConnection(): void
    {
        static::assertInstanceOf(ConnectionInterface::class, $this->wrapper->commit());
    }

    #[Test]
    public function nestedTransactionsCommit(): void
    {
        $nested = 0;

        static::assertFalse($this->wrapper->inTransaction());

        // 1st transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // 2nd transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // 1st commit
        $this->wrapper->commit();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(--$nested, $this->wrapper->getNestedTransactionsCount());

        // 2nd commit
        $this->wrapper->commit();
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(--$nested, $this->wrapper->getNestedTransactionsCount());
    }

    #[Test]
    public function nestedTransactionsRollback(): void
    {
        $nested = 0;

        static::assertFalse($this->wrapper->inTransaction());

        // 1st transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // 2nd transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // Rollback
        $this->wrapper->rollback();
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->wrapper->getNestedTransactionsCount());
    }

    #[Test]
    public function rollbackDisconnectedThrowsException(): void
    {
        $this->wrapper->disconnect();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::DISCONNECTED_ROLLBACK);
        $this->wrapper->rollback();
    }

    #[Test]
    public function rollbackReturnsInstanceOfConnection(): void
    {
        $this->wrapper->beginTransaction();
        static::assertInstanceOf(ConnectionInterface::class, $this->wrapper->rollback());
    }

    #[Test]
    public function rollbackSetsInTransactionAtFalse(): void
    {
        $this->wrapper->beginTransaction();
        $this->wrapper->rollback();
        static::assertFalse($this->wrapper->inTransaction());
    }

    #[Test]
    public function rollbackWithoutBeginThrowsException(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::ROLLBACK_WITHOUT_TRANSACTION);
        $this->wrapper->rollback();
    }

    /**
     * Standalone commit after a SET autocommit=0;
     */
    #[Test]
    public function standaloneCommit(): void
    {
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->wrapper->getNestedTransactionsCount());

        $this->wrapper->commit();

        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->wrapper->getNestedTransactionsCount());
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function setUp(): void
    {
        $this->wrapper = new ConnectionWrapper();
        parent::setUp();
    }
}
