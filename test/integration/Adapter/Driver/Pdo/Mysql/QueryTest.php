<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Mysql;

use Exception;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\Pdo\Result as PdoResult;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Sql;
use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AdapterTrait as BaseAdapterTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Adapter::class, 'query')]
#[CoversMethod(ResultSet::class, 'current')]
class QueryTest extends TestCase
{
    use AdapterTrait;
    use BaseAdapterTrait;

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: array|array<string, mixed>,
     *     2: array<string, mixed>
     * }>
     */
    public static function getQueriesWithRowResult(): array
    {
        return [
            ['SELECT * FROM test WHERE id = ?', [1], ['id' => 1, 'name' => 'foo', 'value' => 'bar']],
            ['SELECT * FROM test WHERE id = :id', [':id' => 1], ['id' => 1, 'name' => 'foo', 'value' => 'bar']],
            ['SELECT * FROM test WHERE id = :id', ['id' => 1], ['id' => 1, 'name' => 'foo', 'value' => 'bar']],
            ['SELECT * FROM test WHERE name = ?', ['123'], ['id' => '4', 'name' => '123', 'value' => 'bar']],
            [
                // name is string, but given parameter is int, can lead to unexpected result
                'SELECT * FROM test WHERE name = ?',
                [123],
                ['id' => '3', 'name' => '123a', 'value' => 'bar'],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('getQueriesWithRowResult')]
    public function testQuery(string $query, array $params, array $expected): void
    {
        /** @todo Have AdapterInterface implement query */
        /** @psalm-suppress UndefinedInterfaceMethod */
        $result = $this->getAdapter()->query($query, $params);
        $this->assertInstanceOf(ResultSet::class, $result);
        $current = $result->current();
        // test as array value
        $this->assertEquals($expected, (array) $current);
        // test as object value
        /** @var string $value */
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $current->$key);
        }
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/288
     *
     * @throws Exception
     */
    public function testSetSessionTimeZone(): void
    {
        /** @todo Have AdapterInterface implement query */
        /** @psalm-suppress UndefinedInterfaceMethod */
        $result = $this->getAdapter()->query('SET @@session.time_zone = :tz', [':tz' => 'SYSTEM']);
        $this->assertInstanceOf(PdoResult::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testSelectWithNotPermittedBindParamName(): void
    {
        $this->expectException(RuntimeException::class);
        /** @todo Have AdapterInterface implement query */
        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->getAdapter()->query('SET @@session.time_zone = :tz$', [':tz$' => 'SYSTEM']);
    }

    /**
     * @see https://github.com/laminas/laminas-db/issues/47
     */
    public function testNamedParameters(): void
    {
        $this->assertNotNull($this->adapter);
        $sql = new Sql($this->adapter);

        $insert = $sql->update('test');
        $insert->set([
            'name'  => ':name',
            'value' => ':value',
        ])->where(['id' => ':id']);
        $stmt = $sql->prepareStatementForSqlObject($insert);
        $this->assertInstanceOf(StatementInterface::class, $stmt);

        //positional parameters
        $stmt->execute([
            'foo',
            'bar',
            1,
        ]);

        //"mapped" named parameters
        $stmt->execute([
            'c_0'    => 'foo',
            'c_1'    => 'bar',
            'where1' => 1,
        ]);

        //real named parameters
        $stmt->execute([
            'id'    => 1,
            'name'  => 'foo',
            'value' => 'bar',
        ]);
    }
}
