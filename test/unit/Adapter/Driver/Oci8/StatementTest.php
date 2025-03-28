<?php

namespace LaminasTest\Db\Adapter\Driver\Oci8;

use Laminas\Db\Adapter\Driver\Oci8\Oci8;
use Laminas\Db\Adapter\Driver\Oci8\Statement;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler\Profiler;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Statement::class, 'setDriver')]
#[CoversMethod(Statement::class, 'setProfiler')]
#[CoversMethod(Statement::class, 'getProfiler')]
#[CoversMethod(Statement::class, 'initialize')]
#[CoversMethod(Statement::class, 'setSql')]
#[CoversMethod(Statement::class, 'setParameterContainer')]
#[CoversMethod(Statement::class, 'getParameterContainer')]
#[CoversMethod(Statement::class, 'getResource')]
#[CoversMethod(Statement::class, 'getSql')]
#[CoversMethod(Statement::class, 'prepare')]
#[CoversMethod(Statement::class, 'isPrepared')]
#[CoversMethod(Statement::class, 'execute')]
#[Group('integrationOracle')]
class StatementTest extends TestCase
{
    protected Statement $statement;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
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

    public function testSetDriver()
    {
        self::assertEquals($this->statement, $this->statement->setDriver(new Oci8([])));
    }

    public function testSetProfiler()
    {
        self::assertEquals($this->statement, $this->statement->setProfiler(new Profiler()));
    }

    public function testGetProfiler()
    {
        $profiler = new Profiler();
        $this->statement->setProfiler($profiler);
        self::assertEquals($profiler, $this->statement->getProfiler());
    }

    public function testInitialize()
    {
        $oci8 = new Oci8([]);
        self::assertEquals($this->statement, $this->statement->initialize($oci8));
    }

    public function testSetSql()
    {
        self::assertEquals($this->statement, $this->statement->setSql('select * from table'));
        self::assertEquals('select * from table', $this->statement->getSql());
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
     * @todo   Implement testGetSql().
     */
    public function testGetSql()
    {
        self::assertEquals($this->statement, $this->statement->setSql('select * from table'));
        self::assertEquals('select * from table', $this->statement->getSql());
    }

    /**
     * @todo   Implement testPrepare().
     */
    public function testPrepare(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testIsPrepared()
    {
        self::assertFalse($this->statement->isPrepared());
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
