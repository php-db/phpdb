<?php

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;

interface SqlInterface
{
    /**
     * Get SQL string for statement
     *
     * @return string
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null);
}
