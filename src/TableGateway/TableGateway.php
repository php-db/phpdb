<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;

use function is_array;

class TableGateway extends AbstractTableGateway
{
    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        TableIdentifier|array|string $table,
        AdapterInterface $adapter,
        Feature\FeatureSet|Feature\AbstractFeature|array $features = new Feature\FeatureSet(),
        ResultSetInterface $resultSetPrototype = new ResultSet(),
        ?Sql $sql = null
    ) {
        $this->table = $table;

        // adapter
        $this->adapter = $adapter;

        /** @phpstan-ignore match.unhandled */
        $this->featureSet = match (true) {
            $features instanceof Feature\AbstractFeature => new Feature\FeatureSet([$features]),
            is_array($features) => new Feature\FeatureSet($features),
        };

        // result prototype
        $this->resultSetPrototype = $resultSetPrototype;

        // Sql object (factory for select, insert, update, delete)
        $this->sql = $sql ?: new Sql($this->adapter, $this->table);

        // check sql object bound to same table
        if ($this->sql->getTable() !== $this->table) {
            throw new Exception\InvalidArgumentException(
                'The table inside the provided Sql object must match the table of this TableGateway'
            );
        }

        $this->initialize();
    }
}
