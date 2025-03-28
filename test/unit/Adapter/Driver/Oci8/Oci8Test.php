<?php

namespace LaminasTest\Db\Adapter\Driver\Oci8;

use Laminas\Db\Adapter\Driver\Oci8\Connection;
use Laminas\Db\Adapter\Driver\Oci8\Oci8;
use Laminas\Db\Adapter\Driver\Oci8\Result;
use Laminas\Db\Adapter\Driver\Oci8\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Oci8::class, 'registerConnection')]
#[CoversMethod(Oci8::class, 'registerStatementPrototype')]
#[CoversMethod(Oci8::class, 'registerResultPrototype')]
#[CoversMethod(Oci8::class, 'getDatabasePlatformName')]
#[CoversMethod(Oci8::class, 'getConnection')]
#[CoversMethod(Oci8::class, 'createStatement')]
#[CoversMethod(Oci8::class, 'createResult')]
#[CoversMethod(Oci8::class, 'getPrepareType')]
#[CoversMethod(Oci8::class, 'formatParameterName')]
#[CoversMethod(Oci8::class, 'getLastGeneratedValue')]
class Oci8Test extends TestCase
{
    /** @var Oci8 */
    protected Oci8 $oci8;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->oci8 = new Oci8([]);
    }

    /**
     * @throws Exception
     */
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
        $mockConnection->expects($this->once())->method('setDriver')->with($this->equalTo($this->oci8));
        self::assertSame($this->oci8, $this->oci8->registerConnection($mockConnection));
    }

    /**
     * @throws Exception
     */
    public function testRegisterStatementPrototype()
    {
        $this->oci8    = new Oci8([]);
        $mockStatement = $this->getMockForAbstractClass(
            Statement::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        $mockStatement->expects($this->once())->method('setDriver')->with($this->equalTo($this->oci8));
        self::assertSame($this->oci8, $this->oci8->registerStatementPrototype($mockStatement));
    }

    /**
     * @throws Exception
     */
    public function testRegisterResultPrototype()
    {
        $this->oci8    = new Oci8([]);
        $mockStatement = $this->getMockForAbstractClass(
            Result::class,
            [],
            '',
            true,
            true,
            true,
            ['setDriver']
        );
        self::assertSame($this->oci8, $this->oci8->registerResultPrototype($mockStatement));
    }

    public function testGetDatabasePlatformName()
    {
        $this->oci8 = new Oci8([]);
        self::assertEquals('Oracle', $this->oci8->getDatabasePlatformName());
        self::assertEquals('Oracle', $this->oci8->getDatabasePlatformName(Oci8::NAME_FORMAT_NATURAL));
    }

    #[Depends('testRegisterConnection')]
    public function testGetConnection()
    {
        $conn = new Connection([]);
        $this->oci8->registerConnection($conn);
        self::assertSame($conn, $this->oci8->getConnection());
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
}
