<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Exception\RuntimeException;
use PhpDb\Sql\Insert;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\SequenceFeature;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class SequenceFeatureTest extends TestCase
{
    protected SequenceFeature $feature;

    protected TableGateway $tableGateway;

    /**  @var string primary key name */
    protected string $primaryKeyField = 'id';

    /** @var string  sequence name */
    protected static string $sequenceName = 'table_sequence';

    #[Override]
    protected function setUp(): void
    {
        $this->feature = new SequenceFeature($this->primaryKeyField, self::$sequenceName);
    }

    private function createTableGatewayWithPlatform(string $platformName, int $sequenceValue = 2): AbstractTableGateway&MockObject
    {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->any())
            ->method('getName')
            ->willReturn($platformName);
        $platform->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnCallback(fn($name) => $name);

        $result = $this->createMock(ResultInterface::class);
        $result->expects($this->any())
            ->method('current')
            ->willReturn(['nextval' => $sequenceValue, 'currval' => $sequenceValue]);

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->any())
            ->method('execute')
            ->willReturn($result);

        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods(['getPlatform', 'createStatement'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->willReturn($platform);
        $adapter->expects($this->any())
            ->method('createStatement')
            ->willReturn($statement);

        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();

        return $tableGateway;
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

    public function testPreInsertWhenPrimaryKeyAlreadyInValues(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['id', 'name']);
        $insert->values([42, 'test']);

        $result = $this->feature->preInsert($insert);

        self::assertSame($insert, $result);

        // Verify sequenceValue was set from the existing value
        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertEquals(42, $sequenceValueProp->getValue($this->feature));
    }

    public function testPreInsertGeneratesSequenceWhenPrimaryKeyNotInValues(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL', 99);
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);

        $result = $this->feature->preInsert($insert);

        self::assertSame($insert, $result);

        // Verify sequenceValue was set from the generated sequence
        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertEquals(99, $sequenceValueProp->getValue($this->feature));

        // Verify the insert now includes the primary key
        $rawState = $insert->getRawState();
        self::assertContains('id', $rawState['columns']);
    }

    public function testPostInsertSetsLastInsertValue(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL', 123);
        $this->feature->setTableGateway($tableGateway);

        // Set up sequenceValue via preInsert
        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);
        $this->feature->preInsert($insert);

        $statement = $this->createMock(StatementInterface::class);
        $result = $this->createMock(ResultInterface::class);

        $this->feature->postInsert($statement, $result);

        // Verify lastInsertValue was set on tableGateway
        self::assertEquals(123, $tableGateway->lastInsertValue);
    }

    #[DataProvider('lastSequenceIdProvider')]
    public function testLastSequenceId(string $platformName): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform($platformName, 55);
        $this->feature->setTableGateway($tableGateway);

        $result = $this->feature->lastSequenceId();

        self::assertEquals(55, $result);
    }

    /** @psalm-return array<array-key, array{0: string}> */
    public static function lastSequenceIdProvider(): array
    {
        return [
            ['PostgreSQL'],
            ['Oracle'],
        ];
    }

    public function testNextSequenceIdThrowsExceptionForUnsupportedPlatform(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('MySQL');
        $this->feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported platform for retrieving next sequence id');

        $this->feature->nextSequenceId();
    }

    public function testLastSequenceIdThrowsExceptionForUnsupportedPlatform(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('MySQL');
        $this->feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported platform for retrieving last sequence id');

        $this->feature->lastSequenceId();
    }

    public function testPostInsertDoesNotSetLastInsertValueWhenSequenceValueIsNull(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');
        $this->feature->setTableGateway($tableGateway);

        // Set initial lastInsertValue via reflection to verify it doesn't change
        $lastInsertValueProp = new ReflectionProperty(AbstractTableGateway::class, 'lastInsertValue');
        $lastInsertValueProp->setValue($tableGateway, 999);

        $statement = $this->createMock(StatementInterface::class);
        $result = $this->createMock(ResultInterface::class);

        // Call postInsert without calling preInsert first, so sequenceValue is null
        $this->feature->postInsert($statement, $result);

        // Verify lastInsertValue was NOT changed (still 999)
        self::assertEquals(999, $lastInsertValueProp->getValue($tableGateway));
    }

    public function testPreInsertWithPrimaryKeyColumnButNullValue(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['id', 'name']);
        $insert->values([null, 'test']); // Primary key exists but is null

        $result = $this->feature->preInsert($insert);

        self::assertSame($insert, $result);

        // Verify sequenceValue was set to null from the existing value
        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertNull($sequenceValueProp->getValue($this->feature));
    }
}
