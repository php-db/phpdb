<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Mysql;

use Laminas\Db\Adapter\Adapter;

use function getenv;
use function is_string;
use function strtolower;

trait AdapterTrait
{
    protected ?string $hostname = 'localhost';

    #[\Override]
    protected function setUp(): void
    {
        if (
            ! is_string(getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL')) ||
            strtolower((string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL')) !== 'true'
        ) {
            $this->markTestSkipped('pdo_mysql integration tests are not enabled!');
        }

        $this->adapter = new Adapter([
            'driver'   => 'pdo_mysql',
            'database' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_DATABASE'),
            'hostname' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_HOSTNAME'),
            'username' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_USERNAME'),
            'password' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_PASSWORD'),
        ]);

        $this->hostname = (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_HOSTNAME');
    }
}
