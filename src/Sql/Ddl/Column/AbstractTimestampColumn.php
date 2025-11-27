<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ExpressionData;

/**
 * @see doc section http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
 */
abstract class AbstractTimestampColumn extends Column
{
    #[Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        if (isset($options['on_update'])) {
            $expressionData
                ->getExpressionPart(0)
                ->addSpecification('%s')
                ->addValue(Argument::literal('ON UPDATE CURRENT_TIMESTAMP'));
        }

        return $expressionData;
    }
}
