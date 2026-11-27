<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\Platform\StatementFactoryInterface;

/**
 * @api
 */
class Sql
{
    protected AdapterInterface $adapter;

    /** @var TableIdentifier|string|array<string, string|TableIdentifier>|null */
    protected TableIdentifier|string|array|null $table;

    protected StatementFactoryInterface $statementFactory;

    /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
    public function __construct(
        AdapterInterface $adapter,
        array|string|TableIdentifier|null $table = null,
    ) {
        $this->table            = $table;
        $this->adapter          = $adapter;
        $this->statementFactory = $adapter->getPlatform()->getStatementFactory();
    }

    /**
     * @param TableIdentifier|string $table
     */
    public function alterTable(TableIdentifier|string $table = ''): Ddl\AlterTable
    {
        return $this->statementFactory->alterTable($table);
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function buildSqlString(SqlInterface $sqlObject, ?AdapterInterface $adapter = null): string
    {
        return $sqlObject->getSqlString(
            $adapter instanceof AdapterInterface ? $adapter->getPlatform() : $this->adapter->getPlatform(),
        );
    }

    /**
     * @param TableIdentifier|string $table
     */
    public function createTable(TableIdentifier|string $table = ''): Ddl\CreateTable
    {
        return $this->statementFactory->createTable($table);
    }

    public function delete(string|TableIdentifier|null $table = null): Delete
    {
        if (null !== $this->table && null !== $table) {
            throw Exception\InvalidArgumentException::forForeignTable($this->table);
        }

        return $this->statementFactory->delete($table ?? $this->table);
    }

    /**
     * @param TableIdentifier|string $table
     */
    public function dropTable(TableIdentifier|string $table = ''): Ddl\DropTable
    {
        return $this->statementFactory->dropTable($table);
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getStatementFactory(): StatementFactoryInterface
    {
        return $this->statementFactory;
    }

    /** @return TableIdentifier|string|array<string, string|TableIdentifier>|null */
    public function getTable(): array|string|TableIdentifier|null
    {
        return $this->table;
    }

    public function hasTable(): bool
    {
        return null !== $this->table;
    }

    public function insert(string|TableIdentifier|null $table = null): Insert
    {
        if (null !== $this->table && null !== $table) {
            throw Exception\InvalidArgumentException::forForeignTable($this->table);
        }

        return $this->statementFactory->insert($table ?? $this->table);
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function prepareStatementForSqlObject(
        PreparableSqlInterface $sqlObject,
        ?StatementInterface $statement = null,
        ?AdapterInterface $adapter = null,
    ): StatementInterface {
        $adapter   ??= $this->adapter;
        $statement ??= $adapter->getDriver()->createStatement();

        $sqlObject->prepareStatement($adapter, $statement);

        return $statement;
    }

    public function select(string|TableIdentifier|null $table = null): Select
    {
        if (null !== $this->table && null !== $table) {
            throw Exception\InvalidArgumentException::forForeignTable($this->table);
        }

        return $this->statementFactory->select($table ?? $this->table);
    }

    /**
     * @param TableIdentifier|string|array<string, string|TableIdentifier> $table
     * @throws Exception\InvalidArgumentException
     */
    public function setTable(array|string|TableIdentifier $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function update(string|TableIdentifier|null $table = null): Update
    {
        if (null !== $this->table && null !== $table) {
            throw Exception\InvalidArgumentException::forForeignTable($this->table);
        }

        return $this->statementFactory->update($table ?? $this->table);
    }
}
