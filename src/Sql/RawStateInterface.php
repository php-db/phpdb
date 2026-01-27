<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface RawStateInterface
{
    /**
     * Get raw state
     *
     * Returns the raw state of the SQL object for cache key generation.
     * When $key is provided, returns only that specific state element.
     *
     * @param string|null $key Optional specific state key to retrieve
     * @return mixed The raw state array or a specific state value
     */
    public function getRawState(?string $key = null): mixed;
}
