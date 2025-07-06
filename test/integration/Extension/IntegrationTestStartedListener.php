<?php

namespace PhpDbIntegrationTest\Extension;

use Exception;
use PhpDbIntegrationTest\Platform\FixtureLoader;
use PhpDbIntegrationTest\Platform\MysqlFixtureLoader;
use PhpDbIntegrationTest\Platform\PgsqlFixtureLoader;
use PhpDbIntegrationTest\Platform\SqlServerFixtureLoader;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

use function getenv;
use function printf;

final class IntegrationTestStartedListener implements StartedSubscriber
{
    /** @var FixtureLoader[] */
    private array $fixtureLoaders = [];

    /**
     * @throws Exception
     */
    public function notify(Started $event): void
    {
        if ($event->testSuite()->name() !== 'integration test') {
            return;
        }

        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL')) {
            $this->fixtureLoaders[] = new PgsqlFixtureLoader();
        }

        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_SQLSRV')) {
            $this->fixtureLoaders[] = new SqlServerFixtureLoader();
        }

        if (empty($this->fixtureLoaders)) {
            return;
        }

        printf("\nIntegration test started.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->createDatabase();
        }
    }
}
