<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Exception\InvalidArgumentException;

use function array_splice;
use function sprintf;
use function strlen;
use function substr;

abstract class AbstractLengthColumn extends Column
{
    /**
     * Whether the type is invalid without a length, as VARCHAR and VARBINARY are in SQL-92 and MySQL.
     */
    protected bool $lengthRequired = false;

    protected ?int $length = null;

    public function __construct(
        string $name,
        ?int $length = null,
        bool $nullable = false,
        mixed $default = null,
        array $options = [],
    ) {
        $this->setLength($length);

        parent::__construct($name, $nullable, $default, $options);
    }

    /**
     * Renders the length in parentheses directly after the type when one is set. Without a length
     * the column renders bare, unless the type requires one.
     *
     * @inheritDoc
     * @throws InvalidArgumentException When the type requires a length and none is set.
     */
    #[Override]
    public function getExpressionData(): array
    {
        $lengthExpression = $this->getLengthExpression();
        $hasLength        = '' !== $lengthExpression && '0' !== $lengthExpression;

        if (! $hasLength && $this->lengthRequired) {
            throw new InvalidArgumentException(sprintf(
                'Column "%s" of type %s requires a length',
                $this->name,
                $this->type,
            ));
        }

        $expressionData = parent::getExpressionData();

        if (! $hasLength) {
            return $expressionData;
        }

        $attributes = substr($expressionData['spec'], strlen($this->specification));

        $expressionData['spec'] = "{$this->specification}(%s){$attributes}";
        array_splice($expressionData['values'], 2, 0, [new Literal($lengthExpression)]);

        return $expressionData;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length = 0): static
    {
        $this->length = $length;

        return $this;
    }

    protected function getLengthExpression(): string
    {
        return (string) $this->length;
    }
}
