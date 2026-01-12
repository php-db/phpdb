<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Exception;

use PhpDb\Exception;

class RuntimeException extends Exception\RuntimeException implements ExceptionInterface
{
}
