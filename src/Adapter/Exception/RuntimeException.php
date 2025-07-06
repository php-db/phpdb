<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use PhpDb\Exception;

class RuntimeException extends Exception\RuntimeException implements ExceptionInterface
{
}
