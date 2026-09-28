<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Exception\InvalidArgumentException;

use function array_splice;
use function ctype_digit;
use function is_int;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

class Integer extends Column
{
    /**
     * Renders the display width from the "length" option in parentheses directly after the type,
     * ahead of the nullability and default clauses.
     *
     * @inheritDoc
     * @throws InvalidArgumentException When the "length" option is not a non-negative integer.
     */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        if (! isset($options['length'])) {
            return $expressionData;
        }

        $displayWidth = $this->normaliseDisplayWidth($options['length']);
        $attributes   = substr($expressionData['spec'], strlen($this->specification));

        $expressionData['spec'] = "{$this->specification}(%s){$attributes}";
        array_splice($expressionData['values'], offset: 2, length: 0, replacement: [new Literal($displayWidth)]);

        return $expressionData;
    }

    /**
     * @throws InvalidArgumentException When the value is not a non-negative integer.
     */
    private function normaliseDisplayWidth(mixed $length): string
    {
        if (is_int($length) && $length >= 0) {
            return (string) $length;
        }

        if (is_string($length) && ctype_digit($length)) {
            return $length;
        }

        throw new InvalidArgumentException(sprintf(
            'Column "%s" length option must be a non-negative integer',
            $this->name,
        ));
    }
}
