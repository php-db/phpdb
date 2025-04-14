<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Mysql;

use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature\MetadataFeature;
use Laminas\Db\TableGateway\TableGateway;
use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AdapterTrait as BaseAdapterTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversMethod(TableGateway::class, '__construct')]
#[CoversMethod(TableGateway::class, 'select')]
#[CoversMethod(TableGateway::class, 'insert')]
final class TableGatewayTest extends TestCase
{
    use AdapterTrait;
    use BaseAdapterTrait;

    public function testConstructor(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter());
        $this->assertInstanceOf(TableGateway::class, $tableGateway);
    }

    public function testSelect(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter());
        $rowset       = $tableGateway->select();

        $this->assertTrue(count($rowset) > 0);
        /** @var object $row */
        foreach ($rowset as $row) {
            $this->assertTrue(isset($row->id));
            $this->assertNotEmpty(isset($row->name));
            $this->assertNotEmpty(isset($row->value));
        }
    }

    public function testInsert(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter());

        $tableGateway->select();
        $data         = [
            'name'  => 'test_name',
            'value' => 'test_value',
        ];
        $affectedRows = $tableGateway->insert($data);
        $this->assertEquals(1, $affectedRows);

        $rowSet = $tableGateway->select(['id' => $tableGateway->getLastInsertValue()]);
        /** @var object $row */
        $row = $rowSet->current();

        foreach ($data as $key => $value) {
            $this->assertEquals($row->$key, $value);
        }
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/35
     * @see https://github.com/zendframework/zend-db/pull/178
     */
    public function testInsertWithExtendedCharsetFieldName(): int
    {
        $tableGateway = new TableGateway('test_charset', $this->getAdapter());

        $affectedRows = $tableGateway->insert([
            'field$' => 'test_value1',
            'field_' => 'test_value2',
        ]);
        $this->assertEquals(1, $affectedRows);

        return $tableGateway->getLastInsertValue();
    }

    #[Depends('testInsertWithExtendedCharsetFieldName')]
    public function testUpdateWithExtendedCharsetFieldName(mixed $id): void
    {
        $tableGateway = new TableGateway('test_charset', $this->getAdapter());

        $data         = [
            'field$' => 'test_value3',
            'field_' => 'test_value4',
        ];
        $affectedRows = $tableGateway->update($data, ['id' => $id]);
        $this->assertEquals(1, $affectedRows);

        $rowSet = $tableGateway->select(['id' => $id]);
        /** @var object $row */
        $row = $rowSet->current();

        foreach ($data as $key => $value) {
            $this->assertEquals($row->$key, $value);
        }
    }

    #[DataProvider('tableProvider')]
    public function testTableGatewayWithMetadataFeature(array|string|TableIdentifier $table): void
    {
        $tableGateway = new TableGateway($table, $this->getAdapter(), new MetadataFeature());

        self::assertInstanceOf(TableGateway::class, $tableGateway);
        self::assertSame($table, $tableGateway->getTable());
    }

    /** @psalm-return array<string, array{0: mixed}> */
    public static function tableProvider(): array
    {
        return [
            'string'                  => ['test'],
            'aliased string'          => [['foo' => 'test']],
            'TableIdentifier'         => [new TableIdentifier('test')],
            'aliased TableIdentifier' => [['foo' => new TableIdentifier('test')]],
        ];
    }
}
