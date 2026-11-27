<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use Override;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;

/**
 * Standard SQL statement factory.
 *
 * A dialect that renders a statement differently subclasses this and overrides the methods
 * whose statements it renders, returning its own statement subclasses.
 */
class AbstractPlatform implements StatementFactoryInterface
{
    #[Override]
    public function alterTable(string|TableIdentifier $table = ''): AlterTable
    {
        return new AlterTable($table);
    }

    #[Override]
    public function createTable(string|TableIdentifier $table = ''): CreateTable
    {
        return new CreateTable($table);
    }

    #[Override]
    public function delete(array|string|TableIdentifier|null $table = null): Delete
    {
        return new Delete($table);
    }

    #[Override]
    public function dropTable(string|TableIdentifier $table = ''): DropTable
    {
        return new DropTable($table);
    }

    #[Override]
    public function insert(array|string|TableIdentifier|null $table = null): Insert
    {
        return new Insert($table);
    }

    #[Override]
    public function select(array|string|TableIdentifier|null $table = null): Select
    {
        return new Select($table);
    }

    #[Override]
    public function update(array|string|TableIdentifier|null $table = null): Update
    {
        return new Update($table);
    }
}
