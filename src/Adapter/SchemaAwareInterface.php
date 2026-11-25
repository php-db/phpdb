<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

/**
 * @api
 */
interface SchemaAwareInterface
{
    /**
     * Get current schema
     */
    public function getCurrentSchema(): string|false;
}
