<?php

namespace Laminas\Db\Adapter\Driver\Pgsql;

/**
 * Closes a PostgreSQL connection
 *
 * @see http://php.net/manual/en/function.pg-close.php
 *
 * @param resource $connection
 */
function pg_close($connection = null): bool
{
    return true;
}
