<?php

namespace PhpDbTest\Adapter\Driver\Pgsql;

use PhpDb\Adapter\Driver\Pgsql\Connection;
use PhpDb\Adapter\Driver\Pgsql\Pgsql;
use PhpDb\Adapter\Driver\Pgsql\Result;
use PhpDb\Adapter\Driver\Pgsql\Statement;
use PhpDb\Adapter\Exception\RuntimeException;
use Override;
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
    #[Override]
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
        $mockConnection = $this->getMockBuilder(Connection::class)
                            ->setConstructorArgs([[]])
                            ->onlyMethods(['setDriver'])
                            ->getMock();
        $mockConnection->expects($this->once())->method('setDriver')->with($this->equalTo($this->pgsql));
        self::assertSame($this->pgsql, $this->pgsql->registerConnection($mockConnection));
    }

    /**
     * @throws Exception
     */
    public function testRegisterStatementPrototype(): void
    {
        $this->pgsql   = new Pgsql([]);
        $mockStatement = $this->getMockBuilder(Statement::class)
                            ->setConstructorArgs([])
                            ->onlyMethods(['setDriver'])
                            ->getMock();
        $mockStatement->expects($this->once())->method('setDriver')->with($this->equalTo($this->pgsql));
        self::assertSame($this->pgsql, $this->pgsql->registerStatementPrototype($mockStatement));
    }

    /**
     * @throws Exception
     */
    public function testRegisterResultPrototype(): void
    {
        $this->pgsql   = new Pgsql([]);
        $mockStatement = $this->getMockBuilder(Result::class)
                            ->setConstructorArgs([])
                            ->onlyMethods([])
                            ->getMock();
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
