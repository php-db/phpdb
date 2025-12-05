<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

class PrimaryKey extends AbstractConstraint
{
    protected string $specification = 'PRIMARY KEY';
}
