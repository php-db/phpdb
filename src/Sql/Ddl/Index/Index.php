<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Index;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;

use function count;
use function implode;
use function str_replace;

class Index extends AbstractIndex
{
    protected string $specification = 'INDEX %s(...)';

    protected array $lengths;

    protected ?string $type = null;

    public function __construct(null|array|string $columns, ?string $name = null, array $lengths = [])
    {
        parent::__construct($columns, $name);

        $this->lengths = $lengths;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $colCount  = count($this->columns);
        $values    = [new Identifier($this->name)];
        $specParts = [];

        for ($i = 0; $i < $colCount; $i++) {
            $specPart = '%s';
            $values[] = new Identifier($this->columns[$i]);

            if (isset($this->lengths[$i])) {
                $specPart .= '(' . $this->lengths[$i] . ')';
            }

            $specParts[] = $specPart;
        }

        $spec = str_replace('...', implode(', ', $specParts), $this->specification);

        if ($this->type !== null) {
            $spec    .= ' USING %s';
            $values[] = new Literal($this->type);
        }

        return [
            'spec'   => $spec,
            'values' => $values,
        ];
    }
}
