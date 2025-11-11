<?php

namespace PhpDb\Sql;

interface ExpressionInterface
{
    public const TYPE_IDENTIFIER = 'identifier';
    public const TYPE_VALUE      = 'value';
    public const TYPE_LITERAL    = 'literal';
    public const TYPE_SELECT     = 'select';

    public function getExpressionData(): ExpressionData;
}
