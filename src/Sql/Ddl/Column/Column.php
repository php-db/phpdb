<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;

use function implode;

class Column implements ColumnInterface
{
    protected string|int|float|bool|Literal|Value|null $default;

    protected bool $isNullable = false;

    protected string $name = '';

    /** @var array<string, mixed> */
    protected array $options = [];

    /** @var list<ConstraintInterface> */
    protected array $constraints = [];

    protected string $specification = '%s %s';

    protected string $type = 'INTEGER';

    /** @param array<string, mixed> $options */
    public function __construct(
        string $name = '',
        bool $nullable = false,
        mixed $default = null,
        array $options = [],
    ) {
        $this->setName($name);
        $this->setNullable($nullable);
        $this->setDefault($default);
        $this->setOptions($options);
    }

    public function addConstraint(ConstraintInterface $constraint): static
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    #[Override]
    public function getDefault(): string|int|float|bool|Literal|Value|null
    {
        return $this->default;
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

        $specParts[] = $this->isNullable ? 'NULL' : 'NOT NULL';

        if (null !== $this->default) {
            $specParts[] = 'DEFAULT %s';
            $values[]    = $this->default instanceof ArgumentInterface
                ? $this->default
                : new Value($this->default);
        }

        if (null === $this->default && $this->isNullable) {
            $specParts[] = 'DEFAULT NULL';
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

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }

    #[Override]
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function setDefault(string|int|float|bool|Literal|Value|null $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function setNullable(bool $nullable): static
    {
        $this->isNullable = $nullable;
        return $this;
    }

    public function setOption(string $name, bool|int|string $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    /** @param array<string, mixed> $options */
    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }
}
