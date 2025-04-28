<?php

namespace LaminasIntegrationTest\Db\Extension;

use Override;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class ListenerExtension implements Extension
{
    #[Override] public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters
    ): void {
        $facade->registerSubscribers(
            new IntegrationTestStartedListener(),
            new IntegrationTestStoppedListener(),
        );
    }
}
