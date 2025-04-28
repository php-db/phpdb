<?php

namespace Laminas\Db\Sql\Ddl\Column;

class Text extends AbstractLengthColumn
{
    protected string $specification = '%s %s';

    protected string $type = 'TEXT';
}
