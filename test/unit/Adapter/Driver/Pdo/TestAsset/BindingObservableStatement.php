<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo\TestAsset;

use PhpDb\Adapter\Driver\Pdo\Statement;

/**
 * Exposes the binding hook and state that Statement keeps for its subclasses.
 */
final class BindingObservableStatement extends Statement
{
    public function bind(): void
    {
        $this->bindParametersFromContainer();
    }

    public function hasBoundParameters(): bool
    {
        return $this->parametersBound;
    }
}
