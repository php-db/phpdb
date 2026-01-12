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
        Feature\FeatureSet|Feature\FeatureInterface|array|null $features = new Feature\FeatureSet(),
        ResultSetInterface|null $resultSetPrototype = new ResultSet(),
        ?Sql $sql = null
    ) {
        $this->table = $table;

        $this->adapter = $adapter;

        $this->featureSet = match (true) {
            $features instanceof Feature\FeatureInterface => new Feature\FeatureSet([$features]),
            is_array($features) => new Feature\FeatureSet($features),
            default => $features,
        };

        $this->resultSetPrototype = $resultSetPrototype;

        $this->sql = $sql ?: new Sql($this->adapter, $this->table);

        if ($this->sql->getTable() !== $this->table) {
            throw new Exception\InvalidArgumentException(
                'The table inside the provided Sql object must match the table of this TableGateway'
            );
        }

        $this->initialize();
    }
}
