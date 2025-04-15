<?php

namespace Laminas\Db\Sql\Predicate;

class NotIn extends In
{
    /** @var string */
    protected string $specification = '%s NOT IN %s';
}
