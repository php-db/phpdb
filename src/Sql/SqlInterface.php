<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;

/**
 * @api
 */
interface SqlInterface
{
    /**
     * Legacy type constants maintained for backward compatibility.
     *
     * @deprecated Use ArgumentType enum instead for type-safe argument handling.
     *
     * @see ArgumentType
     */
    final public const string TYPE_IDENTIFIER = 'identifier';

    final public const string TYPE_VALUE = 'value';

    final public const string TYPE_LITERAL = 'literal';

    final public const string TYPE_SELECT = 'select';

    /**
     * Get SQL string for statement
     *
     * The platform is required: a statement does not know which dialect renders it, and there is
     * no standard SQL fallback.
     */
    public function getSqlString(PlatformInterface $adapterPlatform): string;
}
