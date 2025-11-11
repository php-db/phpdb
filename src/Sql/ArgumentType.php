<?php

declare(strict_types=1);

namespace PhpDb\Sql;

enum ArgumentType: string
{
    case Identifier = 'identifier';
    case Value      = 'value';
    case Literal    = 'literal';
    case Select     = 'select';
}
