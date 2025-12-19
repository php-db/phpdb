<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use Closure;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Join;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDb\Sql\Where;
use PhpDb\TableGateway\Feature\EventFeatureEventsInterface;

use function array_shift;
use function array_values;
use function count;
use function end;
use function is_array;
use function is_object;
use function is_string;
use function reset;
use function sprintf;
use function strtolower;

/**
 * @property AdapterInterface $adapter
 * @property int $lastInsertValue
 * @property string $table
 */
abstract class AbstractTableGateway implements TableGatewayInterface
{
    /** @var bool */
    protected $isInitialized = false;

    /** @var AdapterInterface */
    protected $adapter;

    /** @var string|array|TableIdentifier */
    protected $table;

    /** @var array */
    protected $columns = [];

    protected Feature\FeatureSet $featureSet;

    protected ?ResultSetInterface $resultSetPrototype;

    /** @var Sql */
    protected $sql;

    /** @var int */
    protected $lastInsertValue;

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    /**
     * Initialize
     *
     * @throws Exception\RuntimeException
     */
    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        /** @phpstan-ignore instanceof.alwaysTrue */
        if (! $this->featureSet instanceof Feature\FeatureSet) {
            $this->featureSet = new Feature\FeatureSet();
        }

        $this->featureSet->setTableGateway($this);
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_INITIALIZE, []);

        if (! $this->adapter instanceof AdapterInterface) {
            throw new Exception\RuntimeException('This table does not have an Adapter setup');
        }

        if (! is_string($this->table) && ! $this->table instanceof TableIdentifier && ! is_array($this->table)) {
            throw new Exception\RuntimeException('This table object does not have a valid table set.');
        }

        if (! $this->resultSetPrototype instanceof ResultSetInterface) {
            $this->resultSetPrototype = new ResultSet();
        }

        if (! $this->sql instanceof Sql) {
            $this->sql = new Sql($this->adapter, $this->table);
        }

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_INITIALIZE, []);

        $this->isInitialized = true;
    }

    #[Override]
    public function getTable(): TableIdentifier|array|string
    {
        return $this->table;
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFeatureSet(): Feature\FeatureSet
    {
        return $this->featureSet;
    }

    public function getResultSetPrototype(): ResultSetInterface
    {
        return $this->resultSetPrototype;
    }

    public function getSql(): Sql
    {
        return $this->sql;
    }

    #[Override]
    public function select(Where|Closure|string|array|null $where = null): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select();

        if ($where instanceof Closure) {
            $where($select);
        } elseif ($where !== null) {
            $select->where($where);
        }

        return $this->selectWith($select);
    }

    public function selectWith(Select $select): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        return $this->executeSelect($select);
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function executeSelect(Select $select): ResultSetInterface
    {
        $selectState = $select->getRawState();
        if (
            isset($selectState['table'])
            && $selectState['table'] !== $this->table
            && (is_array($selectState['table'])
                && end($selectState['table']) !== $this->table)
        ) {
            throw new Exception\RuntimeException(
                'The table name of the provided Select object must match that of the table'
            );
        }

        if (
            isset($selectState['columns'])
            && $selectState['columns'] === [Select::SQL_STAR]
            && $this->columns !== []
        ) {
            $select->columns($this->columns);
        }

        // apply preSelect features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_SELECT, [$select]);

        // prepare and execute
        /** @var StatementInterface $statement */
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        // build result set
        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        // apply postSelect features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_SELECT, [$statement, $result, $resultSet]);

        return $resultSet;
    }

    #[Override]
    public function insert(array $set): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $insert = $this->sql->insert();
        $insert->values($set);
        return $this->executeInsert($insert);
    }

    public function insertWith(Insert $insert): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        return $this->executeInsert($insert);
    }

    /**
     * @todo add $columns support
     * @throws Exception\RuntimeException
     */
    protected function executeInsert(Insert $insert): int
    {
        $insertState = $insert->getRawState();
        if ($insertState['table'] !== $this->table) {
            throw new Exception\RuntimeException(
                'The table name of the provided Insert object must match that of the table'
            );
        }

        // apply preInsert features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_INSERT, [$insert]);

        // Most RDBMS solutions do not allow using table aliases in INSERTs
        // See https://github.com/zendframework/zf2/issues/7311
        $unaliasedTable = false;
        if (is_array($insertState['table'])) {
            $tableData      = array_values($insertState['table']);
            $unaliasedTable = array_shift($tableData);
            $insert->into($unaliasedTable);
        }

        /** @var StatementInterface $statement */
        $statement             = $this->sql->prepareStatementForSqlObject($insert);
        $result                = $statement->execute();
        $this->lastInsertValue = $this->adapter->getDriver()->getConnection()->getLastGeneratedValue();

        // apply postInsert features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_INSERT, [$statement, $result]);

        // Reset original table information in Insert instance, if necessary
        if ($unaliasedTable) {
            $insert->into($insertState['table']);
        }

        return $result->getAffectedRows();
    }

    #[Override]
    public function update(
        array $set,
        Where|Closure|array|string|null $where = null,
        ?array $joins = null
    ): int {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $sql    = $this->sql;
        $update = $sql->update();
        $update->set($set);
        if ($where !== null) {
            $update->where($where);
        }

        if ($joins) {
            foreach ($joins as $join) {
                $type = $join['type'] ?? Join::JOIN_INNER;
                $update->join($join['name'], $join['on'], $type);
            }
        }

        return $this->executeUpdate($update);
    }

    public function updateWith(Update $update): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        return $this->executeUpdate($update);
    }

    /**
     * @todo add $columns support
     * @throws Exception\RuntimeException
     */
    protected function executeUpdate(Update $update): int
    {
        $updateState = $update->getRawState();
        if ($updateState['table'] !== $this->table) {
            throw new Exception\RuntimeException(
                'The table name of the provided Update object must match that of the table'
            );
        }

        // apply preUpdate features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_UPDATE, [$update]);

        $unaliasedTable = false;
        if (is_array($updateState['table'])) {
            $tableData      = array_values($updateState['table']);
            $unaliasedTable = array_shift($tableData);
            $update->table($unaliasedTable);
        }

        /** @var StatementInterface $statement */
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        // apply postUpdate features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_UPDATE, [$statement, $result]);

        // Reset original table information in Update instance, if necessary
        if ($unaliasedTable) {
            $update->table($updateState['table']);
        }

        return $result->getAffectedRows();
    }

    #[Override]
    public function delete(Where|Closure|array|string $where): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $delete = $this->sql->delete();
        if ($where instanceof Closure) {
            $where($delete);
        } else {
            $delete->where($where);
        }
        return $this->executeDelete($delete);
    }

    public function deleteWith(Delete $delete): int
    {
        $this->initialize();
        return $this->executeDelete($delete);
    }

    /**
     * @todo add $columns support
     * @throws Exception\RuntimeException
     */
    protected function executeDelete(Delete $delete): int
    {
        $deleteState = $delete->getRawState();
        if ($deleteState['table'] !== $this->table) {
            throw new Exception\RuntimeException(
                'The table name of the provided Delete object must match that of the table'
            );
        }

        // pre delete update
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_DELETE, [$delete]);

        $unaliasedTable = false;
        if (is_array($deleteState['table'])) {
            $tableData      = array_values($deleteState['table']);
            $unaliasedTable = array_shift($tableData);
            $delete->from($unaliasedTable);
        }

        /** @var StatementInterface $statement */
        $statement = $this->sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        // apply postDelete features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_DELETE, [$statement, $result]);

        // Reset original table information in Delete instance, if necessary
        if ($unaliasedTable) {
            $delete->from($deleteState['table']);
        }

        return $result->getAffectedRows();
    }

    public function getLastInsertValue(): int
    {
        return $this->lastInsertValue;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case 'lastinsertvalue':
                return $this->lastInsertValue;
            case 'adapter':
                return $this->adapter;
            case 'table':
                return $this->table;
        }
        if ($this->featureSet->canCallMagicGet($property)) {
            return $this->featureSet->callMagicGet($property);
        }
        throw new Exception\InvalidArgumentException('Invalid magic property access in ' . self::class . '::__get()');
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __set(string $property, mixed $value): void
    {
        if ($this->featureSet->canCallMagicSet($property)) {
            $this->featureSet->callMagicSet($property, $value);
            return;
        }
        throw new Exception\InvalidArgumentException('Invalid magic property access in ' . self::class . '::__set()');
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->featureSet->canCallMagicCall($method)) {
            return $this->featureSet->callMagicCall($method, $arguments);
        }
        throw new Exception\InvalidArgumentException(sprintf(
            'Invalid method (%s) called, caught by %s::__call()',
            $method,
            self::class
        ));
    }

    public function __clone(): void
    {
        $this->resultSetPrototype = isset($this->resultSetPrototype) ? clone $this->resultSetPrototype : null;
        $this->sql                = clone $this->sql;
        if (is_object($this->table)) {
            $this->table = clone $this->table;
        } elseif (
            is_array($this->table)
            && count($this->table) === 1
            && is_object(reset($this->table))
        ) {
            foreach ($this->table as $alias => &$tableObject) {
                $tableObject = clone $tableObject;
            }
        }
    }
}
