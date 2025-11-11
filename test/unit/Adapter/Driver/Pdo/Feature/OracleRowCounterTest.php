<?php

namespace LaminasTest\Db\Adapter\Driver\Pdo\Feature;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\Feature\OracleRowCounter;
use PhpDb\Adapter\Driver\Pdo\Pdo;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\ResultInterface;
use PDO as PDOConnection;
use PDOStatement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversMethod(OracleRowCounter::class, 'getName')]
#[CoversMethod(OracleRowCounter::class, 'getCountForStatement')]
#[CoversMethod(OracleRowCounter::class, 'getCountForSql')]
#[CoversMethod(OracleRowCounter::class, 'getRowCountClosure')]
class OracleRowCounterTest extends TestCase
{
    protected OracleRowCounter $rowCounter;

    #[\Override]
    protected function setUp(): void
    {
        $this->rowCounter = new OracleRowCounter();
    }

    public function testGetName(): void
    {
        self::assertEquals('OracleRowCounter', $this->rowCounter->getName());
    }

    public function testGetCountForStatement(): void
    {
        $statement = $this->getMockStatement('SELECT XXX', 5);
        $statement->expects($this->once())->method('prepare')
            ->with($this->equalTo('SELECT COUNT(*) as "count" FROM (SELECT XXX)'));

        $count = $this->rowCounter->getCountForStatement($statement);
        self::assertEquals(5, $count);
    }

    public function testGetCountForSql(): void
    {
        $this->rowCounter->setDriver($this->getMockDriver(5));
        $count = $this->rowCounter->getCountForSql('SELECT XXX');
        self::assertEquals(5, $count);
    }

    public function testGetRowCountClosure(): void
    {
        $stmt = $this->getMockStatement('SELECT XXX', 5);

        $closure = $this->rowCounter->getRowCountClosure($stmt);
        self::assertInstanceOf('Closure', $closure);
        self::assertEquals(5, $closure());
    }

    /**
     * @param string $sql
     * @param int    $returnValue
     * @return MockObject&Statement
     */
    protected function getMockStatement(string $sql, int $returnValue): MockObject&Statement
    {
        /** @var Statement|MockObject $statement */
        $statement = $this->getMockBuilder(Statement::class)
            ->onlyMethods(['prepare', 'execute'])
            ->disableOriginalConstructor()
            ->getMock();

        // mock PDOStatement with stdClass
        $resource = $this->getMockBuilder(PDOStatement::class)
            ->onlyMethods(['fetch'])
            ->getMock();
        $resource->expects($this->any())
            ->method('fetch')
            ->willReturn(['count' => $returnValue]);

        // mock the result
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $result->expects($this->once())
            ->method('getResource')
            ->willReturn($resource);

        $statement->setSql($sql);
        $statement->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        return $statement;
    }

    /**
     * @psalm-param 5 $returnValue
     */
    protected function getMockDriver(int $returnValue): MockObject&Pdo
    {
        $pdoStatement = $this->getMockBuilder(PDOStatement::class)
            ->onlyMethods(['fetch'])
            ->disableOriginalConstructor()
            ->getMock(); // stdClass can be used here
        $pdoStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => $returnValue]);

        $pdoConnection = $this->getMockBuilder(PDOConnection::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        $pdoConnection->expects($this->any())
            ->method('query')
            ->willReturn($pdoStatement);

        $connection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $connection->expects($this->once())
            ->method('getResource')
            ->willReturn($pdoConnection);

        $driver = $this->getMockBuilder(Pdo::class)
            ->onlyMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $driver->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        return $driver;
    }
}
