<?php

namespace PhpDbIntegrationTest\Platform;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.Interface.Suffix
interface FixtureLoader
{
    public function createDatabase();

    public function dropDatabase();
}
