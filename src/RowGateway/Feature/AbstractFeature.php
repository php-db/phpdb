<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Feature;

use Override;
use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Exception;
use PhpDb\RowGateway\Exception\RuntimeException;

/**
 * @api
 */
abstract class AbstractFeature extends AbstractRowGateway implements FeatureInterface
{
    protected AbstractRowGateway $rowGateway;

    /** @var array<string, mixed> */
    protected array $sharedData = [];

    /** @return array<string, string[]> */
    #[Override]
    public function getMagicMethodSpecifications(): array
    {
        return [];
    }

    #[Override]
    public function getName(): string
    {
        return static::class;
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function initialize(): void
    {
        throw new Exception\RuntimeException('This method is not intended to be called on this object.');
    }

    #[Override]
    public function setRowGateway(AbstractRowGateway $rowGateway): void
    {
        $this->rowGateway = $rowGateway;
    }
}
