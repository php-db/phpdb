<?php

namespace PhpDb\Sql;

class InsertIgnore extends Insert
{
    /** @var array Specification array */
    protected array $specifications = [
        self::SPECIFICATION_INSERT => 'INSERT IGNORE INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'INSERT IGNORE INTO %1$s %2$s %3$s',
    ];
}
