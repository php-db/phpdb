<?php

namespace Laminas\Db\Adapter\Driver\Pdo\Feature;

use Closure;
use Laminas\Db\Adapter\Driver\Feature\AbstractFeature;
use Laminas\Db\Adapter\Driver\Pdo;

use function stripos;

/**
 * SqliteRowCounter
 */
final class SqliteRowCounter extends AbstractFeature
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'SqliteRowCounter';
    }
}
