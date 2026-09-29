<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StatementIntegrationTest extends TestCase
{
    protected Statement $statement;

    protected PDOStatement|MockObject $pdoStatementMock;

    #[Test]
    public function statementExecuteWillConvertPhpBoolToPdoBoolWhenBinding(): void
    {
        $this->pdoStatementMock
            ->expects($this->any())
            ->method('bindParam')
            ->with(
                static::equalTo(':foo'),
                static::equalTo(false),
                static::equalTo(PDO::PARAM_BOOL),
            );
        $this->statement->execute(['foo' => false]);
    }

    #[Test]
    public function statementExecuteWillUsePdoIntForIntWhenBinding(): void
    {
        $this->pdoStatementMock
            ->expects($this->any())
            ->method('bindParam')
            ->with(
                static::equalTo(':foo'),
                static::equalTo(123),
                static::equalTo(PDO::PARAM_INT),
            );
        $this->statement->execute(['foo' => 123]);
    }

    #[Test]
    public function statementExecuteWillUsePdoStrByDefaultWhenBinding(): void
    {
        $this->pdoStatementMock
            ->expects($this->any())
            ->method('bindParam')
            ->with(
                static::equalTo(':foo'),
                static::equalTo('bar'),
                static::equalTo(PDO::PARAM_STR),
            );
        $this->statement->execute(['foo' => 'bar']);
    }

    #[Test]
    public function statementExecuteWillUsePdoStrForStringIntegerWhenBinding(): void
    {
        $this->pdoStatementMock
            ->expects($this->any())
            ->method('bindParam')
            ->with(
                static::equalTo(':foo'),
                static::equalTo('123'),
                static::equalTo(PDO::PARAM_STR),
            );
        $this->statement->execute(['foo' => '123']);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $driver = $this->getMockBuilder(AbstractPdo::class)
            ->onlyMethods(['createResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->statement = new Statement();
        $this->statement->setDriver($driver);
        $this->statement->initialize(new TestAsset\CtorlessPdo(
            $this->pdoStatementMock = $this->getMockBuilder('PDOStatement')
                ->onlyMethods(['execute', 'bindParam'])
                ->getMock(),
        ));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {}
}
