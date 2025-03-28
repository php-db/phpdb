<?php

namespace LaminasTest\Db\Adapter\Driver\IbmDb2;

use Laminas\Db\Adapter\Driver\IbmDb2\IbmDb2;
use Laminas\Db\Adapter\Driver\IbmDb2\Statement;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Laminas\Db\Adapter\ParameterContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

use function error_reporting;

include __DIR__ . '/TestAsset/Db2Functions.php';

#[CoversMethod(Statement::class, 'setDriver')]
#[CoversMethod(Statement::class, 'setParameterContainer')]
#[CoversMethod(Statement::class, 'getParameterContainer')]
#[CoversMethod(Statement::class, 'getResource')]
#[CoversMethod(Statement::class, 'setSql')]
#[CoversMethod(Statement::class, 'getSql')]
#[CoversMethod(Statement::class, 'prepare')]
#[CoversMethod(Statement::class, 'isPrepared')]
#[CoversMethod(Statement::class, 'execute')]
class StatementTest extends TestCase
{
    protected Statement $statement;
    protected int $currentErrorReporting;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // store current error_reporting value as we may change it
        // in a test
        $this->currentErrorReporting = error_reporting();
        $this->statement             = new Statement();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        // ensure error_reporting is set back to correct value
        error_reporting($this->currentErrorReporting);
    }

    public function testSetDriver()
    {
        self::assertEquals($this->statement, $this->statement->setDriver(new IbmDb2([])));
    }

    public function testSetParameterContainer()
    {
        self::assertSame($this->statement, $this->statement->setParameterContainer(new ParameterContainer()));
    }

    /**
     * @todo   Implement testGetParameterContainer().
     */
    public function testGetParameterContainer()
    {
        $container = new ParameterContainer();
        $this->statement->setParameterContainer($container);
        self::assertSame($container, $this->statement->getParameterContainer());
    }

    /**
     * @todo   Implement testGetResource().
     */
    public function testGetResource(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testSetSql().
     */
    public function testSetSql(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetSql().
     */
    public function testGetSql(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testPrepare()
    {
        $sql = "SELECT 'foo' FROM SYSIBM.SYSDUMMY1";
        $this->statement->prepare($sql);
        $this->assertTrue($this->statement->isPrepared());
    }

    public function testPreparingTwiceErrors()
    {
        $sql = "SELECT 'foo' FROM SYSIBM.SYSDUMMY1";
        $this->statement->prepare($sql);
        $this->assertTrue($this->statement->isPrepared());

        $this->expectException(
            RuntimeException::class
        );
        $this->statement->prepare($sql);
    }

    public function testPrepareThrowsRuntimeExceptionOnInvalidSql()
    {
        $sql = "INVALID SQL";
        $this->statement->setSql($sql);

        $this->expectException(
            RuntimeException::class
        );
        $this->statement->prepare();
    }

    /**
     * If error_reporting() is turned off, then the error handler will not
     * be called, but a RuntimeException will still be generated as the
     * resource is false
     */
    public function testPrepareThrowsRuntimeExceptionOnInvalidSqlWithErrorReportingDisabled()
    {
        error_reporting(0);
        $sql = "INVALID SQL";
        $this->statement->setSql($sql);

        $this->expectException(
            RuntimeException::class
        );
        $this->statement->prepare();
    }

    /**
     * @todo   Implement testExecute().
     */
    public function testExecute(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
