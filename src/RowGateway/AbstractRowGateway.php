<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

use ArrayAccess;
use Countable;
use Override;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use ReturnTypeWillChange;

use function array_key_exists;
use function count;

abstract class AbstractRowGateway implements ArrayAccess, Countable, RowGatewayInterface
{
    protected bool $isInitialized = false;

    protected TableIdentifier|string|null $table = null;

    protected ?array $primaryKeyColumn = null;

    protected ?array $primaryKeyData = null;

    protected array $data = [];

    protected ?Sql $sql = null;

    protected ?Feature\FeatureSet $featureSet = null;

    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->data);
    }

    #[Override]
    public function delete(): int
    {
        $this->initialize();

        $where = [];
        foreach ($this->primaryKeyColumn as $pkColumn) {
            $where[$pkColumn] = $this->primaryKeyData[$pkColumn] ?? null;
        }

        // @todo determine if we need to do a select to ensure 1 row will be affected

        $rowsAffected = 0;
        $statement    = $this->sql->prepareStatementForSqlObject($this->sql->delete()->where($where));
        $result       = $statement->execute();

        $rowsAffected = $result->getAffectedRows();
        if ($rowsAffected === 1) {
            $this->primaryKeyData = null;
        }

        return $rowsAffected;
    }

    /**
     * docs: Behaviour has changed - this no longer returns RowGatewayInterface but
     *       instead an array of the old data as per original PHP spec.
     *
     * @return array<string, mixed>
     */
    public function exchangeArray(array $array): array
    {
        $oldData = $this->data;

        $this->populate($array, true);

        return $oldData;
    }

    /**
     * initialize()
     */
    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        if (! $this->featureSet instanceof Feature\FeatureSet) {
            $this->featureSet = new Feature\FeatureSet();
        }

        $this->featureSet->setRowGateway($this);
        $this->featureSet->apply('preInitialize', []);

        if ($this->table === null) {
            throw Exception\RuntimeException::forMissingTable();
        }

        if ($this->primaryKeyColumn === null) {
            throw Exception\RuntimeException::forMissingPrimaryKeyColumn();
        }

        if ($this->sql === null) {
            throw Exception\RuntimeException::forMissingSqlObject();
        }

        $this->featureSet->apply('postInitialize', []);

        $this->isInitialized = true;
    }

    /**
     * Offset Exists
     *
     * @param string $offset
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * Offset get
     *
     * @param string $offset
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * Offset set
     *
     * @param string $offset
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetSet($offset, mixed $value): static
    {
        $this->data[$offset] = $value;

        return $this;
    }

    /**
     * Offset unset
     *
     * @param string $offset
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): static
    {
        $this->data[$offset] = null;

        return $this;
    }

    /**
     * Populate Data
     */
    public function populate(array $rowData, bool $rowExistsInDatabase = false): static
    {
        $this->initialize();

        $this->data = $rowData;
        if ($rowExistsInDatabase === true) {
            $this->processPrimaryKeyData();
        } else {
            $this->primaryKeyData = null;
        }

        return $this;
    }

    public function rowExistsInDatabase(): bool
    {
        return $this->primaryKeyData !== null;
    }

    #[Override]
    public function save(): int
    {
        $this->initialize();

        $rowsAffected = 0;

        if ($this->rowExistsInDatabase()) {
            $data         = $this->data;
            $where        = [];
            $isPkModified = false;

            foreach ($this->primaryKeyColumn as $pkColumn) {
                $where[$pkColumn] = $this->primaryKeyData[$pkColumn];
                if ($data[$pkColumn] === $this->primaryKeyData[$pkColumn]) {
                    unset($data[$pkColumn]);
                } else {
                    $isPkModified = true;
                }
            }

            $statement    = $this->sql->prepareStatementForSqlObject($this->sql->update()->set($data)->where($where));
            $result       = $statement->execute();
            $rowsAffected = $result->getAffectedRows();
            unset($statement, $result);

            if ($isPkModified) {
                foreach ($this->primaryKeyColumn as $pkColumn) {
                    if ($data[$pkColumn] !== $this->primaryKeyData[$pkColumn]) {
                        $where[$pkColumn] = $data[$pkColumn];
                    }
                }
            }
        } else {
            $insert = $this->sql->insert();
            $insert->values($this->data);

            $statement = $this->sql->prepareStatementForSqlObject($insert);
            $result    = $statement->execute();
            if (($primaryKeyValue = $result->getGeneratedValue()) && count($this->primaryKeyColumn) === 1) {
                $this->primaryKeyData = [$this->primaryKeyColumn[0] => $primaryKeyValue];
            } else {
                $this->processPrimaryKeyData();
            }
            $rowsAffected = $result->getAffectedRows();
            unset($statement, $result);

            $where = [];
            foreach ($this->primaryKeyColumn as $pkColumn) {
                $where[$pkColumn] = $this->primaryKeyData[$pkColumn];
            }
        }

        $statement = $this->sql->prepareStatementForSqlObject($this->sql->select()->where($where));
        $result    = $statement->execute();
        $rowData   = $result->current();
        unset($statement, $result);

        $this->populate($rowData, true);

        return $rowsAffected;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processPrimaryKeyData(): void
    {
        $this->primaryKeyData = [];
        foreach ($this->primaryKeyColumn as $column) {
            if (! isset($this->data[$column])) {
                throw Exception\RuntimeException::forMissingPrimaryKeyData($column);
            }
            $this->primaryKeyData[$column] = $this->data[$column];
        }
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        throw Exception\InvalidArgumentException::forInvalidColumn($name);
    }

    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->offsetSet($name, $value);
    }

    public function __unset(string $name): void
    {
        $this->offsetUnset($name);
    }
}
