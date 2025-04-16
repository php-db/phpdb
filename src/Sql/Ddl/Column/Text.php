<?php

namespace Laminas\Db\Sql\Ddl\Column;

class Text extends AbstractLengthColumn
{
    /** @var string */
    protected string $type = 'TEXT';
}
