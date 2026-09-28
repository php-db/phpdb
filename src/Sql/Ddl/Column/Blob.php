<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;

class Blob extends AbstractLengthColumn
{
    /** @var string Change type to blob */
    protected string $type = 'BLOB';

    /**
     * BLOB takes no length, so a configured length is kept but never rendered.
     */
    #[Override]
    protected function getLengthExpression(): string
    {
        return '';
    }
}
