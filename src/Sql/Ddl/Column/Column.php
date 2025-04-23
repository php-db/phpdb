<?php

namespace Laminas\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Ddl\Constraint\ConstraintInterface;

use Laminas\Db\Sql\ExpressionData;

use Laminas\Db\Sql\ExpressionPart;

use function array_merge;

class Column implements ColumnInterface
{
    /** @var null|string|int */
    protected string|int|null $default;

    /** @var bool */
    protected bool $isNullable = false;

    /** @var string */
    protected string $name = '';

    /** @var array */
    protected array $options = [];

    /** @var ConstraintInterface[] */
    protected array $constraints = [];

    /** @var string */
    protected string $specification = '%s %s';

    /** @var string */
    protected string $type = 'INTEGER';

    /**
     * @param null|string $name
     * @param bool        $nullable
     * @param mixed|null  $default
     * @param mixed[]     $options
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
    public function getName()
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
    public function isNullable()
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
    public function getDefault()
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
    public function getOptions()
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

    #[\Override]
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
