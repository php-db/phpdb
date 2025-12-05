<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

class InvalidConnectionParametersException extends RuntimeException implements ExceptionInterface
{
    protected array $parameters;

    public function __construct(string $message, array $parameters)
    {
        parent::__construct($message);
        $this->parameters = $parameters;
    }
}
