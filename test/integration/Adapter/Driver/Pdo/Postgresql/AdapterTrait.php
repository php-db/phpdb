<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Postgresql;

use Laminas\Db\Adapter\Adapter;
use Override;

use function getenv;
use function is_string;
use function strtolower;

trait AdapterTrait
{
    protected ?string $hostname = 'localhost';

    #[Override]
    protected function setUp(): void
    {
        if (
            ! is_string(getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL'))
            || strtolower(getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL')) !== 'true'
        ) {
            $this->markTestSkipped('pdo_pgsql integration tests are not enabled!');
        }

        $this->adapter = new Adapter([
            'driver'   => 'pdo_pgsql',
            'database' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL_DATABASE'),
            'hostname' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL_HOSTNAME'),
            'username' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL_USERNAME'),
            'password' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL_PASSWORD'),
        ]);

        $this->hostname = (string) getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL_HOSTNAME');
    }
}
