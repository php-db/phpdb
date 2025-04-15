<?php

namespace Laminas\Db\Sql\Predicate;

use Laminas\Db\Sql\Expression as BaseExpression;

use function array_slice;
use function func_get_args;
use function is_array;

class Expression extends BaseExpression implements PredicateInterface
{}
