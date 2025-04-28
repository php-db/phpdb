<?php

namespace Laminas\Db\Adapter\Driver\Pdo\Feature;

use Closure;
use Laminas\Db\Adapter\Driver\Feature\AbstractFeature;
use Laminas\Db\Adapter\Driver\Pdo;

use function stripos;

/**
 * OracleRowCounter
 */
final class OracleRowCounter extends AbstractFeature
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'OracleRowCounter';
    }
}
