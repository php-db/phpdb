<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Exception;

use PhpDb\Exception;

class RuntimeException extends Exception\InvalidArgumentException implements ExceptionInterface
{
}
