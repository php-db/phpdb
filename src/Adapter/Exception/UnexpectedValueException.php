<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use PhpDb\Exception;

class UnexpectedValueException extends Exception\UnexpectedValueException implements ExceptionInterface
{
}
