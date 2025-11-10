<?php

namespace PhpDbIntegrationTest\Platform;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.Interface.Suffix
interface FixtureLoader
{
    /**
     * @return void
     */
    public function createDatabase();

    /**
     * @return void
     */
    public function dropDatabase();
}
