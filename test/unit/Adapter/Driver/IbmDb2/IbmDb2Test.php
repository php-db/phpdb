<?php

namespace LaminasTest\Db\Adapter\Driver\IbmDb2;

use Laminas\Db\Adapter\Driver\IbmDb2\Connection;
use Laminas\Db\Adapter\Driver\IbmDb2\IbmDb2;
use Laminas\Db\Adapter\Driver\IbmDb2\Result;
use Laminas\Db\Adapter\Driver\IbmDb2\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversMethod(IbmDb2::class, 'registerConnection')]
#[CoversMethod(IbmDb2::class, 'registerStatementPrototype')]
#[CoversMethod(IbmDb2::class, 'registerResultPrototype')]
#[CoversMethod(IbmDb2::class, 'getDatabasePlatformName')]
#[CoversMethod(IbmDb2::class, 'getConnection')]
#[CoversMethod(IbmDb2::class, 'createStatement')]
#[CoversMethod(IbmDb2::class, 'createResult')]
#[CoversMethod(IbmDb2::class, 'getPrepareType')]
#[CoversMethod(IbmDb2::class, 'formatParameterName')]
#[CoversMethod(IbmDb2::class, 'getLastGeneratedValue')]
#[CoversMethod(IbmDb2::class, 'getResultPrototype')]
class IbmDb2Test extends TestCase
{
    /** @var IbmDb2 */
    protected $ibmdb2;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->ibmdb2 = new IbmDb2([]);
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
        $mockConnection->expects($this->once())->method('setDriver')->with($this->equalTo($this->ibmdb2));
        self::assertSame($this->ibmdb2, $this->ibmdb2->registerConnection($mockConnection));
    }

    public function testRegisterStatementPrototype()
    {
        $this->ibmdb2  = new IbmDb2([]);
        $mockStatement = $this->getMockForAbstractClass(
            Statement::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        $mockStatement->expects($this->once())->method('setDriver')->with($this->equalTo($this->ibmdb2));
        self::assertSame($this->ibmdb2, $this->ibmdb2->registerStatementPrototype($mockStatement));
    }

    public function testRegisterResultPrototype()
    {
        $this->ibmdb2  = new IbmDb2([]);
        $mockStatement = $this->getMockForAbstractClass(
            Result::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        self::assertSame($this->ibmdb2, $this->ibmdb2->registerResultPrototype($mockStatement));
    }

    public function testGetDatabasePlatformName()
    {
        $this->ibmdb2 = new IbmDb2([]);
        self::assertEquals('IbmDb2', $this->ibmdb2->getDatabasePlatformName());
        self::assertEquals('IBM DB2', $this->ibmdb2->getDatabasePlatformName(IbmDb2::NAME_FORMAT_NATURAL));
    }

    #[Depends('testRegisterConnection')]
    public function testGetConnection()
    {
        $conn = new Connection([]);
        $this->ibmdb2->registerConnection($conn);
        self::assertSame($conn, $this->ibmdb2->getConnection());
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
        $resultPrototype = $this->ibmdb2->getResultPrototype();

        self::assertInstanceOf(Result::class, $resultPrototype);
    }
}
