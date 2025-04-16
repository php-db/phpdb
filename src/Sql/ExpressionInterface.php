<?php

namespace Laminas\Db\Sql;

interface ExpressionInterface
{
    public function getExpressionData(): ExpressionData;
}
