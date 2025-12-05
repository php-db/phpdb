<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;

/**
 * @see doc section http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
 */
abstract class AbstractTimestampColumn extends Column
{
    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        if (isset($options['on_update'])) {
            $expressionData['spec']    .= ' %s';
            $expressionData['values'][] = new Literal('ON UPDATE CURRENT_TIMESTAMP');
        }

        return $expressionData;
    }
}
