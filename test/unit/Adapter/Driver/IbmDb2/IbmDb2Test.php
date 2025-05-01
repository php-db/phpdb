<?php

namespace LaminasTest\Db\Adapter\Driver\IbmDb2;

use Laminas\Db\Adapter\Driver\IbmDb2\Connection;
use Laminas\Db\Adapter\Driver\IbmDb2\IbmDb2;
use Laminas\Db\Adapter\Driver\IbmDb2\Result;
use Laminas\Db\Adapter\Driver\IbmDb2\Statement;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
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
final class IbmDb2Test extends TestCase
{
    protected IbmDb2 $ibmdb2;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->ibmdb2 = new IbmDb2([]);
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
        $mockConnection->expects($this->once())->method('setDriver')->with($this->equalTo($this->ibmdb2));
        self::assertSame($this->ibmdb2, $this->ibmdb2->registerConnection($mockConnection));
    }

    /**
     * @throws Exception
     */
    public function testRegisterStatementPrototype(): void
    {
        $this->ibmdb2  = new IbmDb2([]);
        $mockStatement = $this->getMockBuilder(Statement::class)
                            ->setConstructorArgs([])
                            ->onlyMethods(['setDriver'])
                            ->getMock();
        $mockStatement->expects($this->once())->method('setDriver')->with($this->equalTo($this->ibmdb2));
        self::assertSame($this->ibmdb2, $this->ibmdb2->registerStatementPrototype($mockStatement));
    }

    /**
     * @throws Exception
     */
    public function testRegisterResultPrototype(): void
    {
        $this->ibmdb2  = new IbmDb2([]);
        $mockStatement = $this->getMockBuilder(Result::class)->setConstructorArgs([])->onlyMethods([])->getMock();
        self::assertSame($this->ibmdb2, $this->ibmdb2->registerResultPrototype($mockStatement));
    }

    public function testGetDatabasePlatformName(): void
    {
        $this->ibmdb2 = new IbmDb2([]);
        self::assertEquals('IbmDb2', $this->ibmdb2->getDatabasePlatformName());
        self::assertEquals('IBM DB2', $this->ibmdb2->getDatabasePlatformName(IbmDb2::NAME_FORMAT_NATURAL));
    }

    #[Depends('testRegisterConnection')]
    public function testGetConnection(): void
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

    public function testGetResultPrototype(): void
    {
        $resultPrototype = $this->ibmdb2->getResultPrototype();

        self::assertInstanceOf(Result::class, $resultPrototype);
    }
}
