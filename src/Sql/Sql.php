<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\StatementInterface;

class Sql
{
    protected AdapterInterface $adapter;

    protected TableIdentifier|string|array|null $table;

    protected Platform\PlatformDecoratorInterface $sqlPlatform;

    public function __construct(
        AdapterInterface $adapter,
        array|string|TableIdentifier|null $table = null,
    ) {
        $this->table       = $table;
        $this->adapter     = $adapter;
        $this->sqlPlatform = $adapter->getPlatform()->getSqlPlatformDecorator();
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function buildSqlString(SqlInterface $sqlObject, ?AdapterInterface $adapter = null): string
    {
        if (! $this->sqlPlatform instanceof SqlInterface) {
            throw Exception\RuntimeException::forSubjectNotSqlInterface();
        }

        $this->sqlPlatform->setSubject($sqlObject);

        return $this->sqlPlatform->getSqlString(
            $adapter instanceof AdapterInterface ? $adapter->getPlatform() : $this->adapter->getPlatform(),
        );
    }

    public function delete(string|TableIdentifier|null $table = null): Delete
    {
        if (null !== $this->table && null !== $table) {
            throw Exception\InvalidArgumentException::forForeignTable($this->table);
        }

        return new Delete($table ?? $this->table);
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function getSqlPlatform(): ?Platform\PlatformDecoratorInterface
    {
        return $this->sqlPlatform;
    }

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

        return new Insert($table ?? $this->table);
    }

    public function prepareStatementForSqlObject(
        PreparableSqlInterface $sqlObject,
        ?StatementInterface $statement = null,
        ?AdapterInterface $adapter = null,
    ): StatementInterface {
        if (! $this->sqlPlatform instanceof PreparableSqlInterface) {
            throw Exception\RuntimeException::forSubjectNotPreparableSqlInterface();
        }

        $adapter   ??= $this->adapter;
        $statement ??= $adapter->getDriver()->createStatement();

        $this->sqlPlatform->setSubject($sqlObject);
        $this->sqlPlatform->prepareStatement($adapter, $statement);

        return $statement;
    }

    public function select(string|TableIdentifier|null $table = null): Select
    {
        if (null !== $this->table && null !== $table) {
            throw Exception\InvalidArgumentException::forForeignTable($this->table);
        }

        return new Select($table ?? $this->table);
    }

    /**
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

        return new Update($table ?? $this->table);
    }
}
