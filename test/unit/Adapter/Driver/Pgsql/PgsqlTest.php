<?php

namespace LaminasTest\Db\Adapter\Driver\Pgsql;

use Laminas\Db\Adapter\Driver\Pgsql\Connection;
use Laminas\Db\Adapter\Driver\Pgsql\Pgsql;
use Laminas\Db\Adapter\Driver\Pgsql\Result;
use Laminas\Db\Adapter\Driver\Pgsql\Statement;
use Laminas\Db\Adapter\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

#[CoversMethod(Pgsql::class, 'checkEnvironment')]
#[CoversMethod(Pgsql::class, 'registerConnection')]
#[CoversMethod(Pgsql::class, 'registerStatementPrototype')]
#[CoversMethod(Pgsql::class, 'registerResultPrototype')]
#[CoversMethod(Pgsql::class, 'getDatabasePlatformName')]
#[CoversMethod(Pgsql::class, 'getConnection')]
#[CoversMethod(Pgsql::class, 'createStatement')]
#[CoversMethod(Pgsql::class, 'createResult')]
#[CoversMethod(Pgsql::class, 'getPrepareType')]
#[CoversMethod(Pgsql::class, 'formatParameterName')]
#[CoversMethod(Pgsql::class, 'getLastGeneratedValue')]
#[CoversMethod(Pgsql::class, 'getResultPrototype')]
final class PgsqlTest extends TestCase
{
    protected Pgsql $pgsql;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->pgsql = new Pgsql([]);
    }

    public function testCheckEnvironment(): void
    {
        if (! extension_loaded('pgsql')) {
            $this->expectException(RuntimeException::class);
        }
        $this->pgsql->checkEnvironment();
        self::assertTrue(true, 'No exception was thrown');
    }

    /**
     * @throws Exception
     */
    public function testRegisterConnection(): void
    {
        $mockConnection = $this->getMockForAbstractClass(
            Connection::class,
            [[]],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        $mockConnection->expects($this->once())->method('setDriver')->with($this->equalTo($this->pgsql));
        self::assertSame($this->pgsql, $this->pgsql->registerConnection($mockConnection));
    }

    /**
     * @throws Exception
     */
    public function testRegisterStatementPrototype(): void
    {
        $this->pgsql   = new Pgsql([]);
        $mockStatement = $this->getMockForAbstractClass(
            Statement::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        $mockStatement->expects($this->once())->method('setDriver')->with($this->equalTo($this->pgsql));
        self::assertSame($this->pgsql, $this->pgsql->registerStatementPrototype($mockStatement));
    }

    /**
     * @throws Exception
     */
    public function testRegisterResultPrototype(): void
    {
        $this->pgsql   = new Pgsql([]);
        $mockStatement = $this->getMockForAbstractClass(
            Result::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        self::assertSame($this->pgsql, $this->pgsql->registerResultPrototype($mockStatement));
    }

    public function testGetDatabasePlatformName(): void
    {
        $this->pgsql = new Pgsql([]);
        self::assertEquals('Postgresql', $this->pgsql->getDatabasePlatformName());
        self::assertEquals('PostgreSQL', $this->pgsql->getDatabasePlatformName(Pgsql::NAME_FORMAT_NATURAL));
    }

    #[Depends('testRegisterConnection')]
    public function testGetConnection(): void
    {
        $conn = new Connection([]);
        $this->pgsql->registerConnection($conn);
        self::assertSame($conn, $this->pgsql->getConnection());
    }

    /**
     * @todo   Implement testGetPrepareType().
     */
    public function testCreateStatement(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetPrepareType().
     */
    public function testCreateResult(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetPrepareType().
     */
    public function testGetPrepareType(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testFormatParameterName().
     */
    public function testFormatParameterName(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetLastGeneratedValue().
     */
    public function testGetLastGeneratedValue(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetResultPrototype(): void
    {
        $resultPrototype = $this->pgsql->getResultPrototype();

        self::assertInstanceOf(Result::class, $resultPrototype);
    }
}
