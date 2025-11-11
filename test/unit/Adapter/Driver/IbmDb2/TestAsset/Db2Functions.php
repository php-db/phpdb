<?php

namespace PhpDb\Adapter\Driver\IbmDb2;

use function is_string;
use function trigger_error;

use const E_USER_WARNING;

/**
 * Mock db2_prepare by placing in same namespace as Statement
 * Return false if $sql is "invalid sql", otherwise return true
 *
 * @param  resource $connection
 */
function db2_prepare($connection, string $sql, array $options = []): bool
{
    if ($sql === 'INVALID SQL') {
        // db2_prepare issues a warning with invalid SQL
        trigger_error('SQL is invalid', E_USER_WARNING);
        return false;
    }

    return true;
}

/**
 * Mock db2_stmt_errormsg
 * If you pass a string to $stmt, it will be returned to you
 */
function db2_stmt_errormsg(mixed $stmt = null): string
{
    if (is_string($stmt)) {
        return $stmt;
    }

    return 'Error message';
}

/**
 * Mock db2_stmt_error
 * If you pass a string to $stmt, it will be returned to you
 */
function db2_stmt_error(mixed $stmt = null): string
{
    if (is_string($stmt)) {
        return $stmt;
    }

    return '1';
}
