<?php

namespace Laminas\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ExpressionData;
use Override;

abstract class AbstractLengthColumn extends Column
{
    protected string $specification = '%s %s(%s)';

    protected ?int $length = null;

    /**
     * {@inheritDoc}
     *
     * @param int $length
     */
    public function __construct(string $name, ?int $length = null, $nullable = false, $default = null, array $options = [])
    {
        $this->setLength($length);

        parent::__construct($name, $nullable, $default, $options);
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setLength(?int $length = 0): static
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): int|null
    {
        return $this->length;
    }

    protected function getLengthExpression(): string
    {
        return (string) $this->length;
    }

    #[Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = parent::getExpressionData();

        if ($this->getLengthExpression()) {
            $expressionData
                ->getExpressionPart(0)
                ->addValue(Argument::Literal($this->getLengthExpression()));
        }

        return $expressionData;
    }
}
