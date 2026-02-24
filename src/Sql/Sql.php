<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\SqlInterface;

use function sprintf;

class Sql
{
    protected AdapterInterface $adapter;

    protected TableIdentifier|string|array|null $table;

    protected PlatformDecoratorInterface|PreparableSqlInterface $sqlPlatform;

    public function __construct(
        AdapterInterface $adapter,
        array|string|TableIdentifier|null $table = null
    ) {
        $this->adapter     = $adapter;
        $this->table       = $table;
        $this->sqlPlatform = $this->adapter->getPlatform()->getSqlPlatformDecorator();
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function hasTable(): bool
    {
        return $this->table !== null;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setTable(array|string|TableIdentifier $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): array|string|TableIdentifier|null
    {
        return $this->table;
    }

    public function getSqlPlatform(): ?PlatformDecoratorInterface
    {
        return $this->sqlPlatform;
    }

    public function select(string|TableIdentifier|null $table = null): Select
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Select($table ?: $this->table);
    }

    public function insert(string|null|TableIdentifier $table = null): Insert
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Insert($table ?: $this->table);
    }

    public function update(null|string|TableIdentifier $table = null): Update
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Update($table ?: $this->table);
    }

    public function delete(null|string|TableIdentifier $table = null): Delete
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Delete($table ?: $this->table);
    }

    public function prepareStatementForSqlObject(
        PreparableSqlInterface $sqlObject,
        ?StatementInterface $statement = null,
        ?AdapterInterface $adapter = null
    ): StatementInterface {
        $adapter   ??= $this->adapter;
        $statement ??= $adapter->getDriver()->createStatement();

        $this->sqlPlatform->setSubject($sqlObject)->prepareStatement($adapter, $statement);

        return $statement;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function buildSqlString(SqlInterface $sqlObject, ?AdapterInterface $adapter = null): string
    {
        return $this
            ->sqlPlatform
            ->setSubject($sqlObject)
            ->getSqlString(
                $adapter instanceof AdapterInterface ? $adapter->getPlatform() : $this->adapter->getPlatform()
            );
    }
}
