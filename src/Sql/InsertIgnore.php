<?php

declare(strict_types=1);

namespace PhpDb\Sql;

class InsertIgnore extends Insert
{
    protected function getInsertKeyword(): string
    {
        return 'INSERT IGNORE';
    }
}
