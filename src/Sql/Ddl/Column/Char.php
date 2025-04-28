<?php

namespace Laminas\Db\Sql\Ddl\Column;

final class Char extends AbstractLengthColumn
{
    protected string $type = 'CHAR';
}
