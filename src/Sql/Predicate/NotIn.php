<?php

namespace Laminas\Db\Sql\Predicate;

class NotIn extends In
{
    protected string $specification = '%s NOT IN %s';
}
