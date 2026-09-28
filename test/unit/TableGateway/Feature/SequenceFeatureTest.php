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
use PHPUnit\Framework\Attributes\Test;
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

    /** @psalm-return array<array-key, array{0: string}> */
    public static function lastSequenceIdProvider(): array
    {
        return [
            ['PostgreSQL'],
            ['Oracle'],
        ];
    }

    /** @psalm-return array<array-key, array{0: string, 1: string}> */
    public static function nextSequenceIdProvider(): array
    {
        return [
            ['PostgreSQL', 'SELECT NEXTVAL(\'"' . self::$sequenceName . '"\')'],
            ['Oracle', 'SELECT ' . self::$sequenceName . '.NEXTVAL as "nextval" FROM dual'],
        ];
    }

    #[Test]
    #[DataProvider('lastSequenceIdProvider')]
    public function lastSequenceId(string $platformName): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform($platformName, 55);
        $this->feature->setTableGateway($tableGateway);

        $result = $this->feature->lastSequenceId();

        static::assertSame(55, $result);
    }

    #[Test]
    public function lastSequenceIdThrowsExceptionForUnsupportedPlatform(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('MySQL');
        $this->feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported platform for retrieving last sequence id');

        $this->feature->lastSequenceId();
    }

    #[Test]
    public function lastSequenceIdThrowsWhenSequenceHasNoCurrentValue(): void
    {
        $this->feature->setTableGateway($this->createTableGatewayReturning('Oracle', []));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The sequence did not return a current value.');

        $this->feature->lastSequenceId();
    }

    #[Test]
    public function lastSequenceIdThrowsWhenStatementProducesNoResult(): void
    {
        $this->feature->setTableGateway($this->createTableGatewayReturning('Oracle', null));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The sequence statement did not produce a result.');

        $this->feature->lastSequenceId();
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('nextSequenceIdProvider')]
    public function nextSequenceId(string $platformName, string $statementSql): void
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
        $this->tableGateway = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();
        $this->feature->setTableGateway($this->tableGateway);
        $this->feature->nextSequenceId();
    }

    #[Test]
    public function nextSequenceIdReturnsNullWhenSequenceValueIsNotAnInteger(): void
    {
        $this->feature->setTableGateway($this->createTableGatewayReturning('Oracle', ['nextval' => 'abc']));

        static::assertNull($this->feature->nextSequenceId());
    }

    #[Test]
    public function nextSequenceIdThrowsExceptionForUnsupportedPlatform(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('MySQL');
        $this->feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported platform for retrieving next sequence id');

        $this->feature->nextSequenceId();
    }

    #[Test]
    public function nextSequenceIdThrowsWhenSequenceReturnsNoRow(): void
    {
        $this->feature->setTableGateway($this->createTableGatewayReturning('Oracle', 'not-an-array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The sequence did not return a next value.');

        $this->feature->nextSequenceId();
    }

    #[Test]
    public function nextSequenceIdThrowsWhenStatementProducesNoResult(): void
    {
        $this->feature->setTableGateway($this->createTableGatewayReturning('Oracle', null));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The sequence statement did not produce a result.');

        $this->feature->nextSequenceId();
    }

    #[Test]
    public function postInsertDoesNotSetLastInsertValueWhenSequenceValueIsNull(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');
        $this->feature->setTableGateway($tableGateway);

        $lastInsertValueProp = new ReflectionProperty(AbstractTableGateway::class, 'lastInsertValue');
        $lastInsertValueProp->setValue($tableGateway, 999);

        $statement = $this->createMock(StatementInterface::class);
        $result    = $this->createMock(ResultInterface::class);

        $this->feature->postInsert($statement, $result);

        static::assertSame(999, $lastInsertValueProp->getValue($tableGateway));
    }

    #[Test]
    public function postInsertSetsLastInsertValue(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL', 123);
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);
        $this->feature->preInsert($insert);

        $statement = $this->createMock(StatementInterface::class);
        $result    = $this->createMock(ResultInterface::class);

        $this->feature->postInsert($statement, $result);

        static::assertSame(123, $tableGateway->lastInsertValue);
    }

    #[Test]
    public function preInsertGeneratesSequenceWhenPrimaryKeyNotInValues(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL', 99);
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);

        $result = $this->feature->preInsert($insert);

        static::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        static::assertSame(99, $sequenceValueProp->getValue($this->feature));

        $rawState = $insert->getRawState();
        static::assertContains('id', $rawState['columns']);
    }

    #[Test]
    public function preInsertReturnsEarlyWhenNextSequenceIdReturnsNull(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');

        $feature = $this->getMockBuilder(SequenceFeature::class)
            ->setConstructorArgs([$this->primaryKeyField, self::$sequenceName])
            ->onlyMethods(['nextSequenceId'])
            ->getMock();

        $feature->expects($this->once())
            ->method('nextSequenceId')
            ->willReturn(null);

        $feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);

        $result = $feature->preInsert($insert);

        static::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        static::assertNull($sequenceValueProp->getValue($feature));

        $rawState = $insert->getRawState();
        static::assertNotContains('id', $rawState['columns']);
    }

    #[Test]
    public function preInsertThrowsWhenInsertDoesNotExposeArrays(): void
    {
        $insert = $this->createMock(Insert::class);
        $insert->expects($this->any())
            ->method('getRawState')
            ->willReturn('not-an-array');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The insert does not expose columns and values as arrays.');

        $this->feature->preInsert($insert);
    }

    #[Test]
    public function preInsertWhenPrimaryKeyAlreadyInValues(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['id', 'name']);
        $insert->values([42, 'test']);

        $result = $this->feature->preInsert($insert);

        static::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        static::assertSame(42, $sequenceValueProp->getValue($this->feature));
    }

    #[Test]
    public function preInsertWithPrimaryKeyColumnButNullValue(): void
    {
        $tableGateway = $this->createTableGatewayWithPlatform('PostgreSQL');
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['id', 'name']);
        $insert->values([null, 'test']);

        $result = $this->feature->preInsert($insert);

        static::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        static::assertNull($sequenceValueProp->getValue($this->feature));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->feature = new SequenceFeature($this->primaryKeyField, self::$sequenceName);
    }

    private function createTableGatewayReturning(
        string $platformName,
        mixed $current,
    ): AbstractTableGateway&MockObject {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->any())
            ->method('getName')
            ->willReturn($platformName);
        $platform->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn($name) => $name);

        $result = null;
        if (null !== $current) {
            $result = $this->createMock(ResultInterface::class);
            $result->expects($this->any())
                ->method('current')
                ->willReturn($current);
        }

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
        return $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();
    }

    private function createTableGatewayWithPlatform(
        string $platformName,
        int $sequenceValue = 2,
    ): AbstractTableGateway&MockObject {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->any())
            ->method('getName')
            ->willReturn($platformName);
        $platform->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn($name) => $name);

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
        return $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();
    }
}
