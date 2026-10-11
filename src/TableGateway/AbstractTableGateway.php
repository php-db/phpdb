<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use Closure;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Exception\RuntimeException as SqlRuntimeException;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateSet;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDb\Sql\Where;
use PhpDb\TableGateway\Feature\EventFeatureEventsInterface;

use function array_map;
use function array_values;
use function end;
use function in_array;
use function is_array;

/**
 * @api
 * @psalm-import-type ColumnValue from AbstractSql
 * @psalm-import-type ColumnList from Select
 * @psalm-import-type JoinName from Join
 * @psalm-import-type PredicateValue from PredicateSet
 * @psalm-type GatewayTable = TableIdentifier|string|array<string, TableIdentifier|string>
 * @psalm-type GatewayWhere = Where|Closure|string|array<array-key, PredicateValue>
 * @psalm-type GatewayJoin = array{name: JoinName, on: string, type?: string}
 * @property AdapterInterface|null $adapter
 * @property string|int|false|null $lastInsertValue
 * @property GatewayTable|null $table
 */
abstract class AbstractTableGateway implements TableGatewayInterface
{
    protected bool $isInitialized = false;

    protected ?AdapterInterface $adapter = null;

    /** @var GatewayTable|null */
    protected TableIdentifier|string|array|null $table = null;

    /** @var ColumnList */
    protected array $columns = [];

    protected ?Feature\FeatureSet $featureSet = null;

    protected ?ResultSetInterface $resultSetPrototype = null;

    protected ?Sql $sql = null;

    protected string|int|false|null $lastInsertValue = null;

    /**
     * @param GatewayTable|null $table
     *
     * @return GatewayTable|null
     */
    private static function cloneTable(TableIdentifier|string|array|null $table): TableIdentifier|string|array|null
    {
        if ($table instanceof TableIdentifier) {
            return clone $table;
        }

        if (! is_array($table)) {
            return $table;
        }

        return array_map(
            static fn(TableIdentifier|string $aliased): TableIdentifier|string => $aliased instanceof TableIdentifier
                ? clone $aliased
                : $aliased,
            $table,
        );
    }

    /**
     * Returns the bare table of an aliased table array, or null when the table is not aliased.
     *
     * @param GatewayTable $table
     */
    private static function unaliasTable(TableIdentifier|string|array $table): TableIdentifier|string|null
    {
        if (! is_array($table)) {
            return null;
        }

        return array_values($table)[0] ?? null;
    }

    /**
     * @param GatewayWhere $where
     *
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    #[Override]
    public function delete(Where|Closure|array|string $where): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $delete = $this->requireSql()->delete();
        if ($where instanceof Closure) {
            $where($delete);

            return $this->executeDelete($delete);
        }
        $delete->where($where);

        return $this->executeDelete($delete);
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    public function deleteWith(Delete $delete): int
    {
        $this->initialize();

        return $this->executeDelete($delete);
    }

    /** @throws Exception\RuntimeException */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter ?? throw Exception\RuntimeException::forMissingAdapter();
    }

    /** @return ColumnList */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @throws Exception\RuntimeException */
    public function getFeatureSet(): Feature\FeatureSet
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->requireFeatureSet();
    }

    public function getLastInsertValue(): string|int|false|null
    {
        return $this->lastInsertValue;
    }

    /** @throws Exception\RuntimeException */
    public function getResultSetPrototype(): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->requireResultSetPrototype();
    }

    /** @throws Exception\RuntimeException */
    public function getSql(): Sql
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->requireSql();
    }

    /**
     * @return GatewayTable
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function getTable(): TableIdentifier|array|string
    {
        return $this->table ?? throw Exception\RuntimeException::forMissingTable();
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

        $this->featureSet ??= new Feature\FeatureSet();
        $featureSet       = $this->featureSet;

        $featureSet->setTableGateway($this);
        $featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_INITIALIZE, []);

        $adapter = $this->adapter ?? throw Exception\RuntimeException::forMissingAdapter();
        $table   = $this->table ?? throw Exception\RuntimeException::forMissingTable();

        $this->resultSetPrototype ??= new ResultSet();
        $this->sql                ??= new Sql($adapter, $table);

        $featureSet->apply(EventFeatureEventsInterface::EVENT_POST_INITIALIZE, []);

        $this->isInitialized = true;
    }

    /**
     * @param array<string, ColumnValue> $set
     *
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    #[Override]
    public function insert(array $set): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $insert = $this->requireSql()->insert();
        $insert->values($set);

        return $this->executeInsert($insert);
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    public function insertWith(Insert $insert): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->executeInsert($insert);
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    /**
     * @param GatewayWhere|null $where
     *
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    #[Override]
    public function select(Where|Closure|string|array|null $where = null): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        $select = $this->requireSql()->select();

        if ($where instanceof Closure) {
            $where($select);

            return $this->selectWith($select);
        }

        if (null !== $where) {
            $select->where($where);
        }

        return $this->selectWith($select);
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    public function selectWith(Select $select): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->executeSelect($select);
    }

    /**
     * @param array<string, ColumnValue> $set
     * @param GatewayWhere|null          $where
     * @param list<GatewayJoin>|null     $joins
     *
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    #[Override]
    public function update(
        array $set,
        Where|Closure|array|string|null $where = null,
        ?array $joins = null,
    ): int {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $update = $this->requireSql()->update();
        $update->set($set);
        if (null !== $where) {
            $update->where($where);
        }

        foreach ($joins ?? [] as $join) {
            $update->join($join['name'], $join['on'], $join['type'] ?? Join::JOIN_INNER);
        }

        return $this->executeUpdate($update);
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    public function updateWith(Update $update): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->executeUpdate($update);
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     * @todo add $columns support
     */
    protected function executeDelete(Delete $delete): int
    {
        $table = $delete->getRawState('table');
        if ($table !== $this->table) {
            throw Exception\RuntimeException::forTableMismatch('Delete');
        }

        $featureSet = $this->requireFeatureSet();

        // pre delete update
        $featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_DELETE, [$delete]);

        $unaliasedTable = self::unaliasTable($table);
        if (null !== $unaliasedTable) {
            $delete->from($unaliasedTable);
        }

        $statement = $this->requireSql()->prepareStatementForSqlObject($delete);
        $result    = $statement->execute() ?? throw Exception\RuntimeException::forMissingStatementResult('Delete');

        // apply postDelete features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_POST_DELETE, [$statement, $result]);

        // Reset original table information in Delete instance, if necessary
        if (null !== $unaliasedTable) {
            $delete->from($table);
        }

        return $result->getAffectedRows();
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     * @todo add $columns support
     */
    protected function executeInsert(Insert $insert): int
    {
        $table = $insert->getRawState('table');
        if ($table !== $this->table) {
            throw Exception\RuntimeException::forTableMismatch('Insert');
        }

        $featureSet = $this->requireFeatureSet();

        // apply preInsert features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_INSERT, [$insert]);

        // Most RDBMS solutions do not allow using table aliases in INSERTs
        // See https://github.com/zendframework/zf2/issues/7311
        $unaliasedTable = self::unaliasTable($table);
        if (null !== $unaliasedTable) {
            $insert->into($unaliasedTable);
        }

        $statement = $this->requireSql()->prepareStatementForSqlObject($insert);
        $result    = $statement->execute()
            ?? throw Exception\RuntimeException::forMissingStatementResult('Insert');
        $this->lastInsertValue = $this->getAdapter()->getDriver()->getConnection()->getLastGeneratedValue();

        // apply postInsert features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_POST_INSERT, [$statement, $result]);

        // Reset original table information in Insert instance, if necessary
        if (null !== $unaliasedTable) {
            $insert->into($table);
        }

        return $result->getAffectedRows();
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     */
    protected function executeSelect(Select $select): ResultSetInterface
    {
        $table = $select->getRawState(Select::TABLE);
        if (is_array($table) && $table !== $this->table && end($table) !== $this->table) {
            throw Exception\RuntimeException::forTableMismatch('Select');
        }

        if ([Select::SQL_STAR] === $select->getRawState(Select::COLUMNS) && [] !== $this->columns) {
            $select->columns($this->columns);
        }

        $featureSet = $this->requireFeatureSet();

        // apply preSelect features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_SELECT, [$select]);

        // prepare and execute
        $statement = $this->requireSql()->prepareStatementForSqlObject($select);
        $result    = $statement->execute() ?? throw Exception\RuntimeException::forMissingStatementResult('Select');

        // build result set
        $resultSet = clone $this->requireResultSetPrototype();
        $resultSet->initialize($result);

        // apply postSelect features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_POST_SELECT, [$statement, $result, $resultSet]);

        return $resultSet;
    }

    /**
     * @throws Exception\RuntimeException
     * @throws SqlRuntimeException
     * @todo add $columns support
     */
    protected function executeUpdate(Update $update): int
    {
        $table = $update->getRawState('table');
        if ($table !== $this->table) {
            throw Exception\RuntimeException::forTableMismatch('Update');
        }

        $featureSet = $this->requireFeatureSet();

        // apply preUpdate features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_UPDATE, [$update]);

        $unaliasedTable = self::unaliasTable($table);
        if (null !== $unaliasedTable) {
            $update->table($unaliasedTable);
        }

        $statement = $this->requireSql()->prepareStatementForSqlObject($update);
        $result    = $statement->execute() ?? throw Exception\RuntimeException::forMissingStatementResult('Update');

        // apply postUpdate features
        $featureSet->apply(EventFeatureEventsInterface::EVENT_POST_UPDATE, [$statement, $result]);

        // Reset original table information in Update instance, if necessary
        if (null !== $unaliasedTable) {
            $update->table($table);
        }

        return $result->getAffectedRows();
    }

    /** @throws Exception\RuntimeException */
    private function requireFeatureSet(): Feature\FeatureSet
    {
        return $this->featureSet ?? throw Exception\RuntimeException::forUninitializedProperty('featureSet');
    }

    /** @throws Exception\RuntimeException */
    private function requireResultSetPrototype(): ResultSetInterface
    {
        return (
            $this->resultSetPrototype
                ?? throw Exception\RuntimeException::forUninitializedProperty('resultSetPrototype')
        );
    }

    /** @throws Exception\RuntimeException */
    private function requireSql(): Sql
    {
        return $this->sql ?? throw Exception\RuntimeException::forUninitializedProperty('sql');
    }

    /**
     * @param array<array-key, mixed> $arguments
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __call(string $method, array $arguments): mixed
    {
        $featureSet = $this->requireFeatureSet();
        if ($featureSet->canCallMagicCall($method)) {
            return $featureSet->callMagicCall($method, $arguments);
        }
        throw Exception\InvalidArgumentException::forInvalidMagicCall($method, static::class);
    }

    public function __clone(): void
    {
        if (null !== $this->resultSetPrototype) {
            $this->resultSetPrototype = clone $this->resultSetPrototype;
        }

        if (null !== $this->sql) {
            $this->sql = clone $this->sql;
        }

        $this->table = self::cloneTable($this->table);
    }

    /**
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __get(string $property): mixed
    {
        if (in_array($property, ['lastInsertValue', 'adapter', 'table'], strict: true)) {
            return $this->$property;
        }

        $featureSet = $this->requireFeatureSet();
        if ($featureSet->canCallMagicGet($property)) {
            return $featureSet->callMagicGet($property);
        }
        throw Exception\InvalidArgumentException::forInvalidMagicGet(static::class);
    }

    /**
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __set(string $property, mixed $value): void
    {
        $featureSet = $this->requireFeatureSet();
        if ($featureSet->canCallMagicSet($property)) {
            $featureSet->callMagicSet($property, $value);

            return;
        }
        throw Exception\InvalidArgumentException::forInvalidMagicSet(static::class);
    }
}
