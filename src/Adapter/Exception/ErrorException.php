<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use PhpDb\Exception;

class ErrorException extends Exception\ErrorException implements ExceptionInterface
{
}
