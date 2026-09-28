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
            throw new Exception\RuntimeException(
                'This feature '
                    . self::class
                    . ' expects the ResultSet to be an instance of '
                    . RowPrototypeResultSet::class,
            );
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
            throw new Exception\RuntimeException(
                'The table gateway must reference a named table before a RowGateway prototype can be created.',
            );
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
            throw new Exception\RuntimeException(
                'No information was provided to the RowGatewayFeature and/or no MetadataFeature could be consulted '
                    . 'to find the primary key necessary for RowGateway object creation.',
            );
        }

        if (! is_array($metadataData)) {
            throw new Exception\RuntimeException('The MetadataFeature did not expose its metadata as an array.');
        }

        $primaryKey = $metadataData['primaryKey'] ?? null;
        if (! is_string($primaryKey) && ! is_array($primaryKey)) {
            throw new Exception\RuntimeException(
                'The MetadataFeature did not expose a usable primary key for RowGateway object creation.',
            );
        }

        return $primaryKey;
    }
}
