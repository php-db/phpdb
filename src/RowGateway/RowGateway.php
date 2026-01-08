<?php

namespace PhpDb\RowGateway;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;

class RowGateway extends AbstractRowGateway
{
    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        string|array $primaryKeyColumn,
        string|TableIdentifier $table,
        Sql|AdapterInterface|null $adapterOrSql = null
    ) {
        // setup primary key
        $this->primaryKeyColumn = empty($primaryKeyColumn) ? null : (array) $primaryKeyColumn;

        // set table
        $this->table = $table;

        // set Sql object
        if ($adapterOrSql instanceof Sql) {
            $this->sql = $adapterOrSql;
        } elseif ($adapterOrSql instanceof AdapterInterface) {
            $this->sql = new Sql($adapterOrSql, $this->table);
        } else {
            throw new Exception\InvalidArgumentException('A valid Sql object was not provided.');
        }

        if ($this->sql->getTable() !== $this->table) {
            throw new Exception\InvalidArgumentException(
                'The Sql object provided does not have a table that matches this row object'
            );
        }

        $this->initialize();
    }
}
