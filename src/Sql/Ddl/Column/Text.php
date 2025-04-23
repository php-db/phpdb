<?php

namespace Laminas\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;

class Text extends AbstractLengthColumn
{
    protected string $specification = '%s %s';

    /** @var string */
    protected string $type = 'TEXT';


}