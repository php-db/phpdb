<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;

/**
 * Builds the statements a platform renders.
 *
 * The dialect is known when the platform is selected, so it is also known when a statement is
 * created. A platform whose dialect renders a statement differently overrides the method for
 * that statement and returns its own subclass; the statement then carries the dialect for its
 * whole life, including through TableGateway and RowGateway events and subselects.
 *
 * @api
 */
interface StatementFactoryInterface
{
    public function alterTable(string|TableIdentifier $table = ''): AlterTable;

    public function createTable(string|TableIdentifier $table = ''): CreateTable;

    /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
    public function delete(array|string|TableIdentifier|null $table = null): Delete;

    public function dropTable(string|TableIdentifier $table = ''): DropTable;

    /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
    public function insert(array|string|TableIdentifier|null $table = null): Insert;

    /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
    public function select(array|string|TableIdentifier|null $table = null): Select;

    /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
    public function update(array|string|TableIdentifier|null $table = null): Update;
}
