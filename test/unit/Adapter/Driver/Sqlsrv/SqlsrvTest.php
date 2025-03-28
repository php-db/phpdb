<?php

namespace LaminasTest\Db\Adapter\Driver\Sqlsrv;

use Laminas\Db\Adapter\Driver\Sqlsrv\Connection;
use Laminas\Db\Adapter\Driver\Sqlsrv\Result;
use Laminas\Db\Adapter\Driver\Sqlsrv\Sqlsrv;
use Laminas\Db\Adapter\Driver\Sqlsrv\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Sqlsrv::class, 'registerConnection')]
#[CoversMethod(Sqlsrv::class, 'registerStatementPrototype')]
#[CoversMethod(Sqlsrv::class, 'registerResultPrototype')]
#[CoversMethod(Sqlsrv::class, 'getDatabasePlatformName')]
#[CoversMethod(Sqlsrv::class, 'getConnection')]
#[CoversMethod(Sqlsrv::class, 'createStatement')]
#[CoversMethod(Sqlsrv::class, 'createResult')]
#[CoversMethod(Sqlsrv::class, 'getPrepareType')]
#[CoversMethod(Sqlsrv::class, 'formatParameterName')]
#[CoversMethod(Sqlsrv::class, 'getLastGeneratedValue')]
#[CoversMethod(Sqlsrv::class, 'getResultPrototype')]
class SqlsrvTest extends TestCase
{
    /** @var Sqlsrv */
    protected $sqlsrv;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->sqlsrv = new Sqlsrv([]);
    }

    public function testRegisterConnection()
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
        $mockConnection->expects($this->once())->method('setDriver')->with($this->equalTo($this->sqlsrv));
        self::assertSame($this->sqlsrv, $this->sqlsrv->registerConnection($mockConnection));
    }

    public function testRegisterStatementPrototype()
    {
        $this->sqlsrv  = new Sqlsrv([]);
        $mockStatement = $this->getMockForAbstractClass(
            Statement::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        $mockStatement->expects($this->once())->method('setDriver')->with($this->equalTo($this->sqlsrv));
        self::assertSame($this->sqlsrv, $this->sqlsrv->registerStatementPrototype($mockStatement));
    }

    public function testRegisterResultPrototype()
    {
        $this->sqlsrv  = new Sqlsrv([]);
        $mockStatement = $this->getMockForAbstractClass(
            Result::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        self::assertSame($this->sqlsrv, $this->sqlsrv->registerResultPrototype($mockStatement));
    }

    public function testGetDatabasePlatformName()
    {
        $this->sqlsrv = new Sqlsrv([]);
        self::assertEquals('SqlServer', $this->sqlsrv->getDatabasePlatformName());
        self::assertEquals('SQLServer', $this->sqlsrv->getDatabasePlatformName(Sqlsrv::NAME_FORMAT_NATURAL));
    }

    #[Depends('testRegisterConnection')]
    public function testGetConnection()
    {
        $conn = new Connection([]);
        $this->sqlsrv->registerConnection($conn);
        self::assertSame($conn, $this->sqlsrv->getConnection());
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

    public function testGetResultPrototype()
    {
        $resultPrototype = $this->sqlsrv->getResultPrototype();

        self::assertInstanceOf(Result::class, $resultPrototype);
    }
}
