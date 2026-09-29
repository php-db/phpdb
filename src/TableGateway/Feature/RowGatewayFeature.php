<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\RowGateway\RowGateway;
use PhpDb\RowGateway\RowGatewayInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\Exception;

use function is_array;
use function is_string;

final class RowGatewayFeature extends AbstractFeature
{
    /** @var array<array-key, mixed> */
    protected array $constructorArguments = [];

    public function __construct(mixed ...$constructorArguments)
    {
        $this->constructorArguments = $constructorArguments;
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function postInitialize(): void
    {
        $resultSetPrototype = $this->tableGateway->resultSetPrototype;
        if (! $resultSetPrototype instanceof RowPrototypeResultSet) {
            throw Exception\RuntimeException::forUnexpectedResultSet(self::class, RowPrototypeResultSet::class);
        }

        $firstArgument = $this->constructorArguments[0] ?? null;

        if ($firstArgument instanceof RowGatewayInterface) {
            $resultSetPrototype->setRowPrototype($firstArgument);
            return;
        }

        if (null !== $firstArgument && ! is_string($firstArgument)) {
            return;
        }

        $primaryKey = $firstArgument ?? $this->primaryKeyFromMetadata();

        $table = $this->tableGateway->table;
        if (! is_string($table) && ! $table instanceof TableIdentifier) {
            throw Exception\RuntimeException::forUnnamedTableInRowGateway();
        }

        $resultSetPrototype->setRowPrototype(new RowGateway(
            $primaryKey,
            $table,
            $this->tableGateway->adapter,
        ));
    }

    /**
     * @return string|array<array-key, mixed>
     *
     * @throws Exception\RuntimeException
     *
     * @mago-expect analysis:mixed-assignment
     */
    private function primaryKeyFromMetadata(): string|array
    {
        $metadata = $this->tableGateway->featureSet?->getFeatureByClassName(MetadataFeature::class);

        $metadataData = $metadata instanceof MetadataFeature
            ? $metadata->sharedData['metadata'] ?? null
            : null;

        if (null === $metadataData) {
            throw Exception\RuntimeException::forMissingPrimaryKey();
        }

        if (! is_array($metadataData)) {
            throw Exception\RuntimeException::forNonArrayMetadata();
        }

        $primaryKey = $metadataData['primaryKey'] ?? null;
        if (! is_string($primaryKey) && ! is_array($primaryKey)) {
            throw Exception\RuntimeException::forUnusablePrimaryKey();
        }

        return $primaryKey;
    }
}
