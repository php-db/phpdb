<?php

namespace PhpDb\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionData;

/**
 * @see doc section http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
 */
abstract class AbstractTimestampColumn extends Column
{
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        if (isset($options['on_update'])) {
            $expressionData
                ->getExpressionPart(0)
                ->addSpecification('%s')
                ->addValue(new Argument('ON UPDATE CURRENT_TIMESTAMP', ArgumentType::Literal));
        }

        return $expressionData;
    }
}
