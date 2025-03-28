<?php

namespace LaminasTest\Db\Adapter\Driver\Oci8\Feature;

use Closure;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\Oci8\Feature\RowCounter;
use Laminas\Db\Adapter\Driver\Oci8\Oci8;
use Laminas\Db\Adapter\Driver\Oci8\Result;
use Laminas\Db\Adapter\Driver\Oci8\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversMethod(RowCounter::class, 'getName')]
#[CoversMethod(RowCounter::class, 'getCountForStatement')]
#[CoversMethod(RowCounter::class, 'getCountForSql')]
#[CoversMethod(RowCounter::class, 'getRowCountClosure')]
class RowCounterTest extends TestCase
{
    protected RowCounter $rowCounter;

    protected function setUp(): void
    {
        $this->rowCounter = new RowCounter();
    }

    public function testGetName()
    {
        self::assertEquals('RowCounter', $this->rowCounter->getName());
    }

    public function testGetCountForStatement()
    {
        $statement = $this->getMockStatement('SELECT XXX', 5);
        $statement->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo('SELECT COUNT(*) as "count" FROM (SELECT XXX)'));
        $count = $this->rowCounter->getCountForStatement($statement);
        self::assertEquals(5, $count);
    }

    public function testGetCountForSql()
    {
        $this->rowCounter->setDriver($this->getMockDriver(5));
        $count = $this->rowCounter->getCountForSql('SELECT XXX');
        self::assertEquals(5, $count);
    }

    public function testGetRowCountClosure()
    {
        $stmt = $this->getMockStatement('SELECT XXX', 5);
        /** @var Closure $closure */
        $closure = $this->rowCounter->getRowCountClosure($stmt);
        self::assertInstanceOf('Closure', $closure);
        self::assertEquals(5, $closure());
    }

    protected function getMockStatement(string $sql, mixed $returnValue): MockObject&Statement
    {
        $statement = $this->getMockBuilder(Statement::class)
            ->onlyMethods(['prepare', 'execute'])
            ->disableOriginalConstructor()
            ->getMock();

        // mock the result
        $result = $this->getMockBuilder(Result::class)
            ->onlyMethods(['current'])
            ->getMock();
        $result->expects($this->any())
            ->method('current')
            ->willReturn(['count' => $returnValue]);

        $statement->setSql($sql);
        $statement->expects($this->any())
            ->method('execute')
            ->willReturn($result);
        return $statement;
    }

    protected function getMockDriver(mixed $returnValue): Oci8&MockObject
    {
        $oci8Statement = $this->getMockBuilder(Result::class)
            ->onlyMethods(['current'])
            ->disableOriginalConstructor()
            ->getMock(); // stdClass can be used here
        $oci8Statement->expects($this->once())
            ->method('current')
            ->willReturn(['count' => $returnValue]);
        $connection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $connection->expects($this->once())
            ->method('execute')
            ->willReturn($oci8Statement);
        $driver = $this->getMockBuilder(Oci8::class)
            ->onlyMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $driver->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        return $driver;
    }
}
