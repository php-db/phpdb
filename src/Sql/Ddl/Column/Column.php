<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionPart;

class Column implements ColumnInterface
{
    protected string|int|null $default;

    protected bool $isNullable = false;

    protected string $name = '';

    protected array $options = [];

    /** @var ConstraintInterface[] */
    protected array $constraints = [];

    protected string $specification = '%s %s';

    protected string $type = 'INTEGER';

    public function __construct(
        string $name = '',
        bool $nullable = false,
        mixed $default = null,
        array $options = []
    ) {
        $this->setName($name);
        $this->setNullable($nullable);
        $this->setDefault($default);
        $this->setOptions($options);
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    #[Override] public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setNullable(bool $nullable): static
    {
        $this->isNullable = $nullable;
        return $this;
    }

    #[Override] public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setDefault(string|int|null $default): static
    {
        $this->default = $default;
        return $this;
    }

    #[Override] public function getDefault(): string|int|null
    {
        return $this->default;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setOption(string $name, bool|string $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    #[Override] public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addConstraint(ConstraintInterface $constraint): static
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    #[Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionData = new ExpressionData();

        $expressionPart = new ExpressionPart();
        $expressionPart->setSpecification($this->specification);
        $expressionPart->setValues([
            new Argument($this->name, ArgumentType::Identifier),
            new Argument($this->type, ArgumentType::Literal),
        ]);

        if ($this->isNullable === false) {
            $expressionPart->addSpecification('NOT NULL');
        }

        $expressionData->addExpressionPart($expressionPart);

        if ($this->default !== null) {
            $expressionPart = new ExpressionPart();
            $expressionPart->addSpecification('DEFAULT %s');
            $expressionPart->addValue(new Argument($this->default, ArgumentType::Value));
            $expressionData->addExpressionPart($expressionPart);
        }

        foreach ($this->constraints as $constraint) {
            $expressionData->addExpressionParts($constraint->getExpressionData()->getExpressionParts());
        }

        return $expressionData;
    }
}
