<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter;

interface SchemaAwareInterface
{
        /**
     * Get current schema
     *
     * todo: narrow this to string|false when version bumps to PHP 8.2 minimum
     */
    public function getCurrentSchema(): string|bool;
}
