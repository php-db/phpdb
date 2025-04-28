<?php

namespace Laminas\Db\Sql\Ddl\Column;

final class Blob extends AbstractLengthColumn
{
    protected string $specification = '%s %s';

    /** @var string Change type to blob */
    protected string $type = 'BLOB';
}
