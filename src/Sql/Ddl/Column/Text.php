<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;

class Text extends AbstractLengthColumn
{
    protected string $type = 'TEXT';

    /**
     * TEXT takes no length, so a configured length is kept but never rendered.
     */
    #[Override]
    protected function getLengthExpression(): string
    {
        return '';
    }
}
