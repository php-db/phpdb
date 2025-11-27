<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Index;

use Override;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionPart;

use function count;
use function implode;
use function sprintf;
use function str_replace;

class Index extends AbstractIndex
{
    protected string $specification = 'INDEX %s(...)';

    protected array $lengths;

    public function __construct(null|array|string $columns, ?string $name = null, array $lengths = [])
    {
        parent::__construct($columns, $name);

        $this->lengths = $lengths;
    }

    #[Override]
    public function getExpressionData(): ExpressionData
    {
        $colCount = count($this->columns);

        $expressionPart = new ExpressionPart();
        $expressionPart
            ->addValue(Argument::identifier($this->name));

        $specification = [];
        for ($i = 0; $i < $colCount; $i++) {
            $specPart = '%s';
            $expressionPart->addValue(Argument::identifier($this->columns[$i]));

            if (isset($this->lengths[$i])) {
                $specPart .= sprintf('(%s)', $this->lengths[$i]);
            }

            $specification[] = $specPart;
        }

        $expressionPart->addSpecification(str_replace('...', implode(', ', $specification), $this->specification));

        return new ExpressionData($expressionPart);
    }
}
