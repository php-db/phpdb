<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Index;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;

use function implode;
use function str_replace;

class Index extends AbstractIndex
{
    protected string $specification = 'INDEX %s(...)';

    /** @var array<int, int> */
    protected array $lengths;

    protected ?string $type = null;

    /**
     * @param list<string>|string|null $columns
     * @param array<int, int>          $lengths
     */
    public function __construct(array|string|null $columns, ?string $name = null, array $lengths = [])
    {
        parent::__construct($columns, $name);

        $this->lengths = $lengths;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $values    = [new Identifier($this->name)];
        $specParts = [];

        foreach ($this->columns as $i => $column) {
            $specPart = '%s';
            $values[] = new Identifier($column);

            if (null !== ($this->lengths[$i] ?? null)) {
                $specPart .= "({$this->lengths[$i]})";
            }

            $specParts[] = $specPart;
        }

        $spec = str_replace('...', implode(', ', $specParts), $this->specification);

        if (null !== $this->type) {
            $spec     .= ' USING %s';
            $values[] = new Literal($this->type);
        }

        return [
            'spec'   => $spec,
            'values' => $values,
        ];
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }
}
