<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;

use function implode;

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

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    public function setNullable(bool $nullable): static
    {
        $this->isNullable = $nullable;
        return $this;
    }

    #[Override]
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function setDefault(string|int|null $default): static
    {
        $this->default = $default;
        return $this;
    }

    #[Override]
    public function getDefault(): string|int|null
    {
        return $this->default;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function setOption(string $name, bool|string $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    #[Override]
    public function getOptions(): array
    {
        return $this->options;
    }

    public function addConstraint(ConstraintInterface $constraint): static
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $specParts = [$this->specification];
        $values    = [
            new Identifier($this->name),
            new Literal($this->type),
        ];

        if ($this->isNullable === false) {
            $specParts[] = 'NOT NULL';
        }

        if ($this->default !== null) {
            $specParts[] = 'DEFAULT %s';
            $values[]    = new Value($this->default);
        }

        foreach ($this->constraints as $constraint) {
            $constraintData = $constraint->getExpressionData();
            $specParts[]    = $constraintData['spec'];
            foreach ($constraintData['values'] as $value) {
                $values[] = $value;
            }
        }

        return [
            'spec'   => implode(' ', $specParts),
            'values' => $values,
        ];
    }
}
