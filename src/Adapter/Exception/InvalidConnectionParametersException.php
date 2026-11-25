<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

/**
 * @api
 */
class InvalidConnectionParametersException extends RuntimeException
{
    /** @var array<array-key, mixed> */
    protected array $parameters;

    /** @param array<array-key, mixed> $parameters The rejected connection parameters */
    public function __construct(string $message, array $parameters)
    {
        parent::__construct($message);
        $this->parameters = $parameters;
    }
}
