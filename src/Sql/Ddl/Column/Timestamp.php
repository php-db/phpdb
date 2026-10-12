<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Timestamp extends AbstractTimestampColumn
{
    protected string $type = 'TIMESTAMP';
}
