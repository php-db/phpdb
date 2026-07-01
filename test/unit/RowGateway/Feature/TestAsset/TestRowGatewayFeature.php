<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway\Feature\TestAsset;

use PhpDb\RowGateway\Feature\AbstractFeature;

class TestRowGatewayFeature extends AbstractFeature
{
    public bool $called = false;

    /** @var array<mixed> */
    public array $receivedArgs = [];

    public ?string $returnValue = null;

    public function preInitialize(string ...$args): mixed
    {
        $this->called       = true;
        $this->receivedArgs = $args;
        return $this->returnValue;
    }

    public function postInitialize(): void
    {
        $this->called = true;
    }
}
