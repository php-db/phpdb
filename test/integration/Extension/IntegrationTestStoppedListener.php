<?php

namespace LaminasIntegrationTest\Db\Extension;

use LaminasIntegrationTest\Db\Platform\FixtureLoader;
use Override;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;

use function printf;

class IntegrationTestStoppedListener implements FinishedSubscriber
{
    /** @var FixtureLoader[] */
    private array $fixtureLoaders = [];

    #[Override] public function notify(Finished $event): void
    {
        if (
            $event->testSuite()->name() !== 'integration test'
            || $this->fixtureLoaders === []
        ) {
            return;
        }

        printf("\nIntegration test ended.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->dropDatabase();
        }
    }
}
