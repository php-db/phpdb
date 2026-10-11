<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Exception\InvalidArgumentException;

/**
 * @api
 */
abstract class AbstractPrecisionColumn extends AbstractLengthColumn
{
    protected ?int $decimal;

    /** @param array<string, mixed> $options */
    public function __construct(
        string $name,
        ?int $digits = null,
        ?int $decimal = null,
        bool $nullable = false,
        string|int|float|bool|Literal|Value|null $default = null,
        array $options = [],
    ) {
        $this->setDecimal($decimal);

        parent::__construct($name, $digits, $nullable, $default, $options);
    }

    public function getDecimal(): ?int
    {
        return $this->decimal;
    }

    public function getDigits(): ?int
    {
        return $this->getLength();
    }

    public function setDecimal(?int $decimal): static
    {
        $this->decimal = $decimal;

        return $this;
    }

    public function setDigits(?int $digits): static
    {
        return $this->setLength($digits);
    }

    /**
     * @throws InvalidArgumentException When a decimal scale is set without digits.
     */
    #[Override]
    protected function getLengthExpression(): string
    {
        return match (true) {
            null === $this->decimal => (string) $this->length,
            null === $this->length => throw InvalidArgumentException::forMissingDecimalDigits($this->name, $this->type),
            default => "{$this->length},{$this->decimal}",
        };
    }
}
