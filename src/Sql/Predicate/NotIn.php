<?php

namespace PhpDb\Sql\Predicate;

class NotIn extends In
{
    /** @var string */
    protected $specification = '%s NOT IN %s';
}
