<?php

declare(strict_types=1);

namespace PhpDb\RowGateway;

use ArrayAccess;
use Countable;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\Exception\RuntimeException as SqlRuntimeException;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use ReturnTypeWillChange;

use function array_key_exists;
use function count;
use function is_array;

/**
 * @api
 * @implements ArrayAccess<string, mixed>
 */
abstract class AbstractRowGateway implements ArrayAccess, Countable, RowGatewayInterface
{
    protected bool $isInitialized = false;

    protected TableIdentifier|string|null $table = null;

    /** @var list<string>|null */
    protected ?array $primaryKeyColumn = null;

    /** @var array<string, mixed>|null */
    protected ?array $primaryKeyData = null;

    /** @var array<array-key, mixed> */
    protected array $data = [];

    protected ?Sql $sql = null;

    protected ?Feature\FeatureSet $featureSet = null;

    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    #[Override]
    public function delete(): int
    {
        $this->initialize();

        $sql   = $this->requireSql();
        $where = [];
        foreach ($this->requirePrimaryKeyColumn() as $pkColumn) {
            $where[$pkColumn] = $this->primaryKeyData[$pkColumn] ?? null;
        }

        // @todo determine if we need to do a select to ensure 1 row will be affected

        $statement = $sql->prepareStatementForSqlObject($sql->delete()->where($where));

        $rowsAffected = $this->executeStatement($statement)->getAffectedRows();
        if (1 === $rowsAffected) {
            $this->primaryKeyData = null;
        }

        return $rowsAffected;
    }

    /**
     * docs: Behaviour has changed - this no longer returns RowGatewayInterface but
     *       instead an array of the old data as per original PHP spec.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     *
     * @throws Exception\RuntimeException
     */
    public function exchangeArray(array $array): array
    {
        $oldData = $this->data;

        $this->populate($array, true);

        return $oldData;
    }

    /**
     * initialize()
     *
     * @throws Exception\RuntimeException
     */
    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->featureSet ??= new Feature\FeatureSet();
        $featureSet       = $this->featureSet;

        $featureSet->setRowGateway($this);
        $featureSet->apply('preInitialize', []);

        if (null === $this->table) {
            throw Exception\RuntimeException::forMissingTable();
        }

        $this->requirePrimaryKeyColumn();
        $this->requireSql();

        $featureSet->apply('postInitialize', []);

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
        return $this->data[$offset] ?? null;
    }

    /**
     * Offset set
     *
     * Returns the row for chaining, where ArrayAccess declares void.
     *
     * @param string $offset
     *
     * @mago-expect analysis:incompatible-return-type
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
     * Returns the row for chaining, where ArrayAccess declares void.
     *
     * @param string $offset
     *
     * @mago-expect analysis:incompatible-return-type
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
     *
     * @param array<array-key, mixed> $data
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function populate(array $data, bool $rowExistsInDatabase = false): static
    {
        $this->initialize();

        $this->data           = $data;
        $this->primaryKeyData = null;
        if ($rowExistsInDatabase) {
            $this->processPrimaryKeyData();
        }

        return $this;
    }

    public function rowExistsInDatabase(): bool
    {
        return null !== $this->primaryKeyData;
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     *
     * @mago-expect analysis:mixed-assignment
     */
    #[Override]
    public function save(): int
    {
        $this->initialize();

        $sql = $this->requireSql();

        [$rowsAffected, $where] = $this->rowExistsInDatabase()
            ? $this->updateRow($sql)
            : $this->insertRow($sql);

        $statement = $sql->prepareStatementForSqlObject($sql->select()->where($where));
        $rowData   = $this->executeStatement($statement)->current();
        if (! is_array($rowData)) {
            throw Exception\RuntimeException::forMissingSavedRow();
        }

        $this->populate($rowData, true);

        return $rowsAffected;
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processPrimaryKeyData(): void
    {
        $primaryKeyData = [];
        foreach ($this->requirePrimaryKeyColumn() as $column) {
            $primaryKeyData[$column] = $this->data[$column]
                ?? throw Exception\RuntimeException::forMissingPrimaryKeyData($column);
        }
        $this->primaryKeyData = $primaryKeyData;
    }

    /**
     * @throws Exception\RuntimeException
     */
    private function executeStatement(StatementInterface $statement): ResultInterface
    {
        return $statement->execute() ?? throw Exception\RuntimeException::forMissingStatementResult();
    }

    /**
     * Inserts the row and returns the affected row count with the WHERE that reselects it.
     *
     * @return array{int, array<string, mixed>}
     *
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    private function insertRow(Sql $sql): array
    {
        $primaryKeyColumn = $this->requirePrimaryKeyColumn();

        $insert = $sql->insert();
        $insert->values($this->data);

        $result = $this->executeStatement($sql->prepareStatementForSqlObject($insert));
        $this->processInsertedPrimaryKey($primaryKeyColumn, $result->getGeneratedValue());

        $where = [];
        foreach ($primaryKeyColumn as $pkColumn) {
            $where[$pkColumn] = $this->primaryKeyData[$pkColumn] ?? null;
        }

        return [$result->getAffectedRows(), $where];
    }

    /**
     * Takes a single-column primary key from the generated value, or every column from the row data.
     *
     * @param list<string> $primaryKeyColumn
     *
     * @throws Exception\RuntimeException
     */
    private function processInsertedPrimaryKey(array $primaryKeyColumn, string|int|false|null $generatedValue): void
    {
        if ($generatedValue && 1 === count($primaryKeyColumn)) {
            $this->primaryKeyData = [$primaryKeyColumn[0] => $generatedValue];

            return;
        }

        $this->processPrimaryKeyData();
    }

    /**
     * @return list<string>
     *
     * @throws Exception\RuntimeException
     */
    private function requirePrimaryKeyColumn(): array
    {
        return $this->primaryKeyColumn ?? throw Exception\RuntimeException::forMissingPrimaryKeyColumn();
    }

    /** @throws Exception\RuntimeException */
    private function requireSql(): Sql
    {
        return $this->sql ?? throw Exception\RuntimeException::forMissingSqlObject();
    }

    /**
     * Updates the row and returns the affected row count with the WHERE that reselects it.
     *
     * @return array{int, array<string, mixed>}
     *
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    private function updateRow(Sql $sql): array
    {
        $primaryKeyColumn = $this->requirePrimaryKeyColumn();
        $primaryKeyData   = $this->primaryKeyData ?? [];
        $data             = $this->data;
        $where            = [];

        foreach ($primaryKeyColumn as $pkColumn) {
            $where[$pkColumn] = $primaryKeyData[$pkColumn] ?? null;
            if (($data[$pkColumn] ?? null) === $where[$pkColumn]) {
                unset($data[$pkColumn]);
            }
        }

        $statement    = $sql->prepareStatementForSqlObject($sql->update()->set($data)->where($where));
        $rowsAffected = $this->executeStatement($statement)->getAffectedRows();

        foreach ($primaryKeyColumn as $pkColumn) {
            if (! array_key_exists($pkColumn, $data)) {
                continue;
            }
            $where[$pkColumn] = $data[$pkColumn];
        }

        return [$rowsAffected, $where];
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
