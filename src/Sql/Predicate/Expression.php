<?php

namespace Laminas\Db\Sql\Predicate;

use Laminas\Db\Sql\Expression as BaseExpression;

use function array_slice;
use function func_get_args;
use function is_array;

class Expression extends BaseExpression implements PredicateInterface
{
    /**
     * Constructor
     *
     * @param string|null                 $expression
     * @param int|float|bool|string|array $valueParameter
     */
    public function __construct(string $expression = null, int|float|bool|string|array $valueParameter = null) /*[, $valueParameter, ... ]*/
    {
        if ($expression !== null) {
            parent::__construct($expression);
        }

        $this->setParameters(is_array($valueParameter) ? $valueParameter : array_slice(func_get_args(), 1));
    }
}
