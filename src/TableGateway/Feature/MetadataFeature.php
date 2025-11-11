<?php

namespace PhpDb\TableGateway\Feature;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Source\Factory as SourceFactory;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\Exception;

use function count;
use function current;
use function is_array;

class MetadataFeature extends AbstractFeature
{
    /** @var MetadataInterface */
    protected $metadata;

    /**
     * Constructor
     */
    public function __construct(?MetadataInterface $metadata = null)
    {
        if ($metadata instanceof \PhpDb\Metadata\MetadataInterface) {
            $this->metadata = $metadata;
        }
        $this->sharedData['metadata'] = [
            'primaryKey' => null,
            'columns'    => [],
        ];
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function postInitialize()
    {
        if ($this->metadata === null) {
            $this->metadata = SourceFactory::createSourceFromAdapter($this->tableGateway->adapter);
        }

        // localize variable for brevity
        $t = $this->tableGateway;
        $m = $this->metadata;

        $tableGatewayTable = is_array($t->table) ? current($t->table) : $t->table;

        if ($tableGatewayTable instanceof TableIdentifier) {
            $table  = $tableGatewayTable->getTable();
            $schema = $tableGatewayTable->getSchema();
        } else {
            $table  = $tableGatewayTable;
            $schema = null;
        }

        // get column named
        $columns    = $m->getColumnNames($table, $schema);
        $t->columns = $columns;

        // set locally
        $this->sharedData['metadata']['columns'] = $columns;

        // process primary key only if table is a table; there are no PK constraints on views
        if (! $m->getTable($table, $schema) instanceof TableObject) {
            return;
        }

        $pkc = null;

        foreach ($m->getConstraints($table, $schema) as $constraint) {
            if ($constraint->getType() === 'PRIMARY KEY') {
                $pkc = $constraint;
                break;
            }
        }

        if ($pkc === null) {
            throw new Exception\RuntimeException('A primary key for this column could not be found in the metadata.');
        }

        $pkcColumns = $pkc->getColumns();
        $primaryKey = count($pkcColumns) === 1 ? $pkcColumns[0] : $pkcColumns;

        $this->sharedData['metadata']['primaryKey'] = $primaryKey;
    }
}
