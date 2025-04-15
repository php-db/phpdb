<?php

namespace Laminas\Db\Sql;

use Laminas\Db\Adapter\Platform\PlatformInterface;

interface SqlInterface
{
    public const  TYPE_IDENTIFIER = 'identifier';
    public const  TYPE_VALUE      = 'value';
    public const  TYPE_LITERAL    = 'literal';
    public const  TYPE_SELECT     = 'select';

    /**
     * Get SQL string for statement
     *
     * @return string
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null);
}
