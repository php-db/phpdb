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

    /**
     * @param null|string $name
     * @param bool        $nullable
     * @param mixed|null  $default
     */
    public function __construct($name = null, $nullable = false, $default = null, array $options = [])
    {
        $this->setName($name);
        $this->setNullable($nullable);
        $this->setDefault($default);
        $this->setOptions($options);
    }

    /**
     * @param  string $name
     * @return $this Provides a fluent interface
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * @return null|string
     */
    #[Override] public function getName()
    {
        return $this->name;
    }

    /**
     * @param  bool $nullable
     * @return $this Provides a fluent interface
     */
    public function setNullable($nullable)
    {
        $this->isNullable = (bool) $nullable;
        return $this;
    }

    /**
     * @return bool
     */
    #[Override] public function isNullable()
    {
        return $this->isNullable;
    }

    /**
     * @param  null|string|int $default
     * @return $this Provides a fluent interface
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return null|string|int
     */
    #[Override] public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param  string $name
     * @param  string|boolean $value
     * @return $this Provides a fluent interface
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    #[Override] public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addConstraint(ConstraintInterface $constraint)
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
