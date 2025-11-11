<?php

namespace PhpDb\Sql\Ddl\Column;

use Override;

abstract class AbstractPrecisionColumn extends AbstractLengthColumn
{
    protected ?int $decimal;

    /**
     * {@inheritDoc}
     *
     * @param int|null $decimal
     * @param int      $digits
     */
    public function __construct(
        $name,
        $digits = null,
        $decimal = null,
        $nullable = false,
        $default = null,
        array $options = []
    ) {
        $this->setDecimal($decimal);

        parent::__construct($name, $digits, $nullable, $default, $options);
    }

    /**
     * @param  int $digits
     * @return $this
     */
    public function setDigits($digits)
    {
        return $this->setLength($digits);
    }

    public function getDigits(): int|null
    {
        return $this->getLength();
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setDecimal(?int $decimal)
    {
        $this->decimal = $decimal;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDecimal()
    {
        return $this->decimal;
    }

    /**
     * {}
     * @return string
     */
    #[Override] protected function getLengthExpression(): string
    {
        if ($this->decimal !== null) {
            return $this->length . ',' . $this->decimal;
        }

        return $this->length;
    }
}
