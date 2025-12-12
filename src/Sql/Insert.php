<?php

declare(strict_types=1);

namespace PhpDb\Sql;

final class Insert extends AbstractInsert
{
    protected function getInsertKeyword(): string
    {
        return 'INSERT';
    }
}
