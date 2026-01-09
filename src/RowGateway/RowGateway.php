<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;

use function is_string;

class RowGateway extends AbstractRowGateway
{
    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        string|array|null $primaryKeyColumn,
        string|TableIdentifier $table,
        Sql|AdapterInterface $adapterOrSql
    ) {
        // setup primary key
        if (is_string($primaryKeyColumn)) {
            $primaryKeyColumn = $primaryKeyColumn !== '' ? (array) $primaryKeyColumn : null;
        }
        $this->primaryKeyColumn = $primaryKeyColumn;

        // set table
        $this->table = $table;

        // set Sql object
        if ($adapterOrSql instanceof Sql) {
            $this->sql = $adapterOrSql;
        } else {
            $this->sql = new Sql($adapterOrSql, $this->table);
        }

        if ($this->sql->getTable() !== $this->table) {
            throw new Exception\InvalidArgumentException(
                'The Sql object provided does not have a table that matches this row object'
            );
        }

        $this->initialize();
    }
}
