<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;

abstract class AbstractPrecisionColumn extends AbstractLengthColumn
{
    protected ?int $decimal;

    public function __construct(
        string $name,
        ?int $digits = null,
        ?int $decimal = null,
        bool $nullable = false,
        mixed $default = null,
        array $options = []
    ) {
        $this->setDecimal($decimal);

        parent::__construct($name, $digits, $nullable, $default, $options);
    }

    public function setDigits(?int $digits): static
    {
        return $this->setLength($digits);
    }

    public function getDigits(): int|null
    {
        return $this->getLength();
    }

    public function setDecimal(?int $decimal): static
    {
        $this->decimal = $decimal;

        return $this;
    }

    public function getDecimal(): ?int
    {
        return $this->decimal;
    }

    #[Override]
    protected function getLengthExpression(): string
    {
        if ($this->decimal !== null) {
            return $this->length . ',' . $this->decimal;
        }

        return (string) $this->length;
    }
}
