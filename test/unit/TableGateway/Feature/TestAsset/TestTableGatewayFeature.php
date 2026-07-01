<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature\TestAsset;

use PhpDb\TableGateway\Feature\AbstractFeature;

class TestTableGatewayFeature extends AbstractFeature
{
    public bool $called = false;

    /** @var array<mixed> */
    public array $receivedArgs = [];

    public ?string $returnValue = null;

    /** @var array<string, array<int, string>> */
    public array $magicMethodSpecs = [];

    public function testMethod(mixed ...$args): mixed
    {
        $this->called       = true;
        $this->receivedArgs = $args;
        return $this->returnValue;
    }

    public function customMethod(array $args): string
    {
        $this->called       = true;
        $this->receivedArgs = $args;
        return 'result: ' . ($args[0] ?? 'default');
    }

    /** @return array<string, array<int, string>> */
    public function getMagicMethodSpecifications(): array
    {
        return $this->magicMethodSpecs;
    }
}
