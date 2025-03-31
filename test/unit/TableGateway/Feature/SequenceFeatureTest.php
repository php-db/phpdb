<?php

namespace LaminasTest\Db\TableGateway\Feature;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

final class SequenceFeatureTest extends TestCase
{
    protected SequenceFeature $feature;

    protected TableGateway $tableGateway;

    /**  @var string primary key name */
    protected string $primaryKeyField = 'id';

    /** @var string  sequence name */
    protected static string $sequenceName = 'table_sequence';

    #[\Override]
    protected function setUp(): void
    {
        $this->feature = new SequenceFeature($this->primaryKeyField, self::$sequenceName);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('nextSequenceIdProvider')]
    public function testNextSequenceId(string $platformName, string $statementSql): void
    {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->any())
            ->method('getName')
            ->willReturn($platformName);
        $platform->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturn(self::$sequenceName);
        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods(['getPlatform', 'createStatement'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->willReturn($platform);
        $result = $this->createMock(ResultInterface::class);
        $result->expects($this->any())
            ->method('current')
            ->willReturn(['nextval' => 2]);
        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->any())
            ->method('execute')
            ->willReturn($result);
        $statement->expects($this->any())
            ->method('prepare')
            ->with($statementSql);
        $adapter->expects($this->once())
            ->method('createStatement')
            ->willReturn($statement);
        $this->tableGateway = $this
            ->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();
        $this->feature->setTableGateway($this->tableGateway);
        $this->feature->nextSequenceId();
    }

    /** @psalm-return array<array-key, array{0: string, 1: string}> */
    public static function nextSequenceIdProvider(): array
    {
        return [
            ['PostgreSQL', 'SELECT NEXTVAL(\'"' . self::$sequenceName . '"\')'],
            ['Oracle', 'SELECT ' . self::$sequenceName . '.NEXTVAL as "nextval" FROM dual'],
        ];
    }
}
