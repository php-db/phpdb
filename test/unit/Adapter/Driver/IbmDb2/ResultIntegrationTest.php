<?php

namespace LaminasTest\Db\Adapter\Driver\IbmDb2;

use Laminas\Db\Adapter\Driver\IbmDb2\Result;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Result::class, 'initialize')]
#[CoversMethod(Result::class, 'buffer')]
#[CoversMethod(Result::class, 'getResource')]
#[CoversMethod(Result::class, 'current')]
#[CoversMethod(Result::class, 'next')]
#[CoversMethod(Result::class, 'key')]
#[CoversMethod(Result::class, 'rewind')]
#[CoversMethod(Result::class, 'valid')]
#[CoversMethod(Result::class, 'count')]
#[CoversMethod(Result::class, 'getFieldCount')]
#[CoversMethod(Result::class, 'isQueryResult')]
#[CoversMethod(Result::class, 'getAffectedRows')]
#[CoversMethod(Result::class, 'getGeneratedValue')]
#[Group('integration')]
#[Group('integration-ibm_db2')]
final class ResultIntegrationTest extends TestCase
{
    protected Result $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Result();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * @todo   Implement testInitialize().
     */
    public function testInitialize(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testBuffer().
     */
    public function testBuffer(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
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
     * @todo   Implement testCurrent().
     */
    public function testCurrent(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testNext().
     */
    public function testNext(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testKey().
     */
    public function testKey(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testRewind().
     */
    public function testRewind(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testValid().
     */
    public function testValid(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testCount().
     */
    public function testCount(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetFieldCount().
     */
    public function testGetFieldCount(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testIsQueryResult().
     */
    public function testIsQueryResult(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetAffectedRows().
     */
    public function testGetAffectedRows(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetGeneratedValue().
     */
    public function testGetGeneratedValue(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
