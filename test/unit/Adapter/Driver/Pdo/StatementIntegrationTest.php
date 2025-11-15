<?php

namespace PhpDbTest\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StatementIntegrationTest extends TestCase
{
    protected Statement $statement;

    /** @var MockObject */
    protected PDOStatement|MockObject $pdoStatementMock;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $driver = $this->getMockBuilder(AbstractPdo::class)
            ->onlyMethods(['createResult', 'getDatabasePlatformName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->statement = new Statement();
        $this->statement->setDriver($driver);
        $this->statement->initialize(new TestAsset\CtorlessPdo(
            $this->pdoStatementMock = $this->getMockBuilder('PDOStatement')
                ->onlyMethods(['execute', 'bindParam'])
                ->getMock()
        ));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testStatementExecuteWillConvertPhpBoolToPdoBoolWhenBinding(): void
    {
        $this->pdoStatementMock->expects($this->any())->method('bindParam')->with(
            $this->equalTo(':foo'),
            $this->equalTo(false),
            $this->equalTo(PDO::PARAM_BOOL)
        );
        $this->statement->execute(['foo' => false]);
    }

    public function testStatementExecuteWillUsePdoStrByDefaultWhenBinding(): void
    {
        $this->pdoStatementMock->expects($this->any())->method('bindParam')->with(
            $this->equalTo(':foo'),
            $this->equalTo('bar'),
            $this->equalTo(PDO::PARAM_STR)
        );
        $this->statement->execute(['foo' => 'bar']);
    }

    public function testStatementExecuteWillUsePdoStrForStringIntegerWhenBinding(): void
    {
        $this->pdoStatementMock->expects($this->any())->method('bindParam')->with(
            $this->equalTo(':foo'),
            $this->equalTo('123'),
            $this->equalTo(PDO::PARAM_STR)
        );
        $this->statement->execute(['foo' => '123']);
    }

    public function testStatementExecuteWillUsePdoIntForIntWhenBinding(): void
    {
        $this->pdoStatementMock->expects($this->any())->method('bindParam')->with(
            $this->equalTo(':foo'),
            $this->equalTo(123),
            $this->equalTo(PDO::PARAM_INT)
        );
        $this->statement->execute(['foo' => 123]);
    }
}
