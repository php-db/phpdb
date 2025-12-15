<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ClauseInterface;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\TableIdentifier;

final class From implements ClauseInterface
{
    protected TableIdentifier $table;

    public function __construct(string|array|TableIdentifier $table)
    {
        $this->table = TableIdentifier::from($table);
    }

    public function getTable(): TableIdentifier
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->table->getAlias();
    }

    /**
     * Get the table reference name (alias if set, otherwise table name)
     */
    public function getTableReference(): string
    {
        return $this->table->getReference();
    }

    /**
     * Build FROM clause.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        return $this->table->toFromSqlPart($builder);
    }
}
