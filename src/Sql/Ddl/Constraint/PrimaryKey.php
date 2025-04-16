<?php

namespace Laminas\Db\Sql\Ddl\Constraint;

class PrimaryKey extends AbstractConstraint
{
    protected string $specification = 'PRIMARY KEY';
}
