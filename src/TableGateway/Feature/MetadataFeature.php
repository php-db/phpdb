<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\Exception;

use function count;
use function current;
use function is_array;
use function is_string;
use function reset;

/**
 * @api
 */
class MetadataFeature extends AbstractFeature
{
    /**
     * Constructor
     */
    public function __construct(
        protected MetadataInterface $metadata,
    ) {
        $this->sharedData['metadata'] = [
            'primaryKey' => null,
            'columns'    => [],
        ];
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function postInitialize(): void
    {
        // localize variable for brevity
        $t = $this->tableGateway;
        $m = $this->metadata;

        $tableGatewayTable = $t->getTable();
        if (is_array($tableGatewayTable)) {
            $tableGatewayTable = current($tableGatewayTable);
        }

        if (! $tableGatewayTable instanceof TableIdentifier && ! is_string($tableGatewayTable)) {
            throw new Exception\RuntimeException(
                'The table gateway must reference a named table before metadata can be resolved.',
            );
        }

        $table = $tableGatewayTable instanceof TableIdentifier
            ? $tableGatewayTable->getTable()
            : $tableGatewayTable;

        $schema = $tableGatewayTable instanceof TableIdentifier
            ? $tableGatewayTable->getSchema()
            : null;

        // get column named
        $columns    = $m->getColumnNames($table, $schema);
        $t->columns = $columns;

        // set locally
        $metadata                     = $this->sharedData['metadata'] ?? [];
        $metadata                     = is_array($metadata) ? $metadata : [];
        $metadata['columns']          = $columns;
        $this->sharedData['metadata'] = $metadata;

        // process primary key only if table is a table; there are no PK constraints on views
        if (! $m->getTable($table, $schema) instanceof TableObject) {
            return;
        }

        $pkc = null;

        foreach ($m->getConstraints($table, $schema) as $constraint) {
            if ($constraint->getType() !== 'PRIMARY KEY') {
                continue;
            }

            $pkc = $constraint;
            break;
        }

        if (null === $pkc) {
            throw new Exception\RuntimeException('A primary key for this column could not be found in the metadata.');
        }

        $pkcColumns = $pkc->getColumns();
        $primaryKey = 1 === count($pkcColumns) ? reset($pkcColumns) : $pkcColumns;

        $metadata['primaryKey']       = $primaryKey;
        $this->sharedData['metadata'] = $metadata;
    }
}
