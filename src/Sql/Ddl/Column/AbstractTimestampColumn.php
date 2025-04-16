<?php

namespace Laminas\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\ExpressionData;
use Laminas\Db\Sql\ExpressionPart;

use function array_merge;

/**
 * @see doc section http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
 */
abstract class AbstractTimestampColumn extends Column
{
    /**
     * @return array
     */
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = parent::getExpressionData();
        $options = $this->getOptions();

        if (isset($options['on_update'])) {
            $expressionData
                ->getExpressionPart(0)
                ->addSpecification('%s')
                ->addValue(new Argument('ON UPDATE CURRENT_TIMESTAMP', ArgumentType::Literal));
        }

        return $expressionData;
    }
}
