<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDbTest\ResultSet\TestAsset\PassThroughResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractResultSet::class, 'currentRow')]
final class AbstractResultSetIntegrationTest extends TestCase
{
    protected AbstractResultSet $resultSet;

    /**
     * @throws \Exception
     */
    #[Test]
    public function currentCallsDataSourceCurrentAsManyTimesWithoutBuffer(): void
    {
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->resultSet->initialize($result);
        $result->method('valid')->willReturn(true);
        $result->expects($this->exactly(3))->method('current')->willReturn(['foo' => 'bar']);
        // Call current() multiple times and verify data source is called each time
        $value1 = $this->resultSet->current();
        $value2 = $this->resultSet->current();
        $this->resultSet->current();
        static::assertEquals($value1, $value2);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function currentCallsDataSourceCurrentOnceWithBuffer(): void
    {
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->resultSet->buffer();
        $this->resultSet->initialize($result);
        $result->method('valid')->willReturn(true);
        $result->expects($this->once())->method('current')->willReturn(['foo' => 'bar']);
        // Call current() multiple times and verify data source is called only once due to buffering
        $value1 = $this->resultSet->current();
        $value2 = $this->resultSet->current();
        $this->resultSet->current();
        static::assertEquals($value1, $value2);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        $this->resultSet = new PassThroughResultSet();
    }
}
