<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;

use function array_splice;

abstract class AbstractLengthColumn extends Column
{
    protected string $specification = '%s %s(%s)';

    protected ?int $length = null;

    public function __construct(
        string $name,
        ?int $length = null,
        bool $nullable = false,
        mixed $default = null,
        array $options = []
    ) {
        $this->setLength($length);

        parent::__construct($name, $nullable, $default, $options);
    }

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

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();

        if ($this->getLengthExpression() !== '' && $this->getLengthExpression() !== '0') {
            array_splice($expressionData['values'], 2, 0, [new Literal($this->getLengthExpression())]);
        }

        return $expressionData;
    }
}
