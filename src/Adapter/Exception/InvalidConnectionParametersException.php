<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

class InvalidConnectionParametersException extends RuntimeException implements ExceptionInterface
{
    /** @var int */
    protected $parameters;

    public function __construct(string $message, int $parameters)
    {
        parent::__construct($message);
        $this->parameters = $parameters;
    }
}
