<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\TableGateway\AbstractTableGateway;

interface FeatureInterface
{
    public function getName(): string;

    public function setTableGateway(AbstractTableGateway $tableGateway): void;

    /** @return array<string, string[]> */
    public function getMagicMethodSpecifications(): array;
}
