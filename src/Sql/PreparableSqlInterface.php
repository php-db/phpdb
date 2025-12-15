<?php

declare(strict_types=1);

namespace PhpDb\Sql;

interface PreparableSqlInterface
{
    /**
     * Build the SQL string with quoted identifiers and bound/quoted values.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string;
}
