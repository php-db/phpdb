<?php

namespace Laminas\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\ExpressionData;

class Integer extends Column
{
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        if (isset($options['length'])) {
            $expressionData
                ->getExpressionPart(0)
                ->addSpecification(sprintf('(%s)', $options['length']));
        }

        return $expressionData;
    }
}
