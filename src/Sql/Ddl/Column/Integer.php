<?php

namespace PhpDb\Sql\Ddl\Column;

use PhpDb\Sql\ExpressionData;

use function sprintf;

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
