<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;

interface FeatureInterface
{
    public function getName(): string;

    public function setRowGateway(AbstractRowGateway $rowGateway): void;

    /** @return array<string, string[]> */
    public function getMagicMethodSpecifications(): array;
}
