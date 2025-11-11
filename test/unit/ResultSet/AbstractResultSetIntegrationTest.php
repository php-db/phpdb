<?php

namespace PhpDbTest\ResultSet;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\AbstractResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractResultSet::class, 'current')]
class AbstractResultSetIntegrationTest extends TestCase
{
    protected AbstractResultSet|MockObject $resultSet;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->resultSet = $this->getMockBuilder(AbstractResultSet::class)->onlyMethods([])->getMock();
    }

    public function testCurrentCallsDataSourceCurrentAsManyTimesWithoutBuffer(): void
    {
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->resultSet->initialize($result);
        $result->expects($this->exactly(3))->method('current')->willReturn(['foo' => 'bar']);
        $value1 = $this->resultSet->current();
        $value2 = $this->resultSet->current();
        $this->resultSet->current();
        self::assertEquals($value1, $value2);
    }

    public function testCurrentCallsDataSourceCurrentOnceWithBuffer(): void
    {
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->resultSet->buffer();
        $this->resultSet->initialize($result);
        $result->expects($this->once())->method('current')->willReturn(['foo' => 'bar']);
        $value1 = $this->resultSet->current();
        $value2 = $this->resultSet->current();
        $this->resultSet->current();
        self::assertEquals($value1, $value2);
    }
}
