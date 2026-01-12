<?php

declare(strict_types=1);

namespace PhpDb\Feature;

interface FeatureInterface
{
    public function getName(): string;

    /** @return array<string, string[]> */
    public function getMagicMethodSpecifications(): array;
}
