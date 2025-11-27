<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

enum ResultSetReturnType: string
{
    case ArrayObject = 'arrayobject';
    case Array       = 'array';
}
