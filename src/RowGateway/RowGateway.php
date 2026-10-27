<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;

use function is_string;

/**
 * @api
 */
class RowGateway extends AbstractRowGateway
{
    /**
     * Constructor
     *
     * @param string|list<string>|null $primaryKeyColumn
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __construct(
        string|array|null $primaryKeyColumn,
        string|TableIdentifier $table,
        Sql|AdapterInterface $adapterOrSql,
    ) {
        // setup primary key
        if (is_string($primaryKeyColumn)) {
            $primaryKeyColumn = '' === $primaryKeyColumn ? null : [$primaryKeyColumn];
        }
        $this->primaryKeyColumn = $primaryKeyColumn;

        // set table
        $this->table = $table;

        // set Sql object
        $this->sql = $adapterOrSql instanceof Sql ? $adapterOrSql : new Sql($adapterOrSql, $this->table);

        if ($this->sql->getTable() !== $this->table) {
            throw Exception\InvalidArgumentException::forSqlTableMismatch();
        }

        $this->initialize();
    }
}
