<?php

declare(strict_types=1);

namespace PhpDb\Sql;

/**
 * @psalm-import-type Specification from AbstractSql
 */
class InsertIgnore extends Insert
{
    /** @var array<string, Specification> */
    protected array $specifications = [
        self::SPECIFICATION_INSERT => 'INSERT IGNORE INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'INSERT IGNORE INTO %1$s %2$s %3$s',
    ];
}
