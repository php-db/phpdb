<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;

interface SqlInterface
{
    /**
     * Legacy type constants maintained for backward compatibility.
     *
     * @deprecated Use ArgumentType enum instead for type-safe argument handling.
     *
     * @see ArgumentType
     */
    public const string TYPE_IDENTIFIER = 'identifier';

    public const string TYPE_VALUE = 'value';

    public const string TYPE_LITERAL = 'literal';

    public const string TYPE_SELECT = 'select';

    /**
     * Get SQL string for statement
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string;
}
