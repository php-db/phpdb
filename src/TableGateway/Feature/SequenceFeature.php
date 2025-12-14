<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Exception\RuntimeException;
use PhpDb\Sql\Insert;

use function array_search;

class SequenceFeature extends AbstractFeature
{
    /** @var string */
    protected $primaryKeyField;

    /** @var string */
    protected $sequenceName;

    /** @var int */
    protected $sequenceValue;

    public function __construct(string $primaryKeyField, string $sequenceName)
    {
        $this->primaryKeyField = $primaryKeyField;
        $this->sequenceName    = $sequenceName;
    }

    /**
     * @return Insert
     */
    public function preInsert(Insert $insert)
    {
        $columns = $insert->getRawState('columns');
        $values  = $insert->getRawState('values');
        $key     = array_search($this->primaryKeyField, $columns);
        if ($key !== false) {
            $this->sequenceValue = $values[$key] ?? null;
            return $insert;
        }

        $this->sequenceValue = $this->nextSequenceId();
        if ($this->sequenceValue === null) {
            return $insert;
        }

        $insert->values([$this->primaryKeyField => $this->sequenceValue], Insert::VALUES_MERGE);
        return $insert;
    }

    public function postInsert(StatementInterface $statement, ResultInterface $result): void
    {
        if ($this->sequenceValue !== null) {
            $this->tableGateway->lastInsertValue = $this->sequenceValue;
        }
    }

    /**
     * Generate a new value from the specified sequence in the database, and return it.
     *
     * @throws RuntimeException
     */
    public function nextSequenceId(): int
    {
        $platform     = $this->tableGateway->adapter->getPlatform();
        $platformName = $platform->getName();

        $sql = match ($platformName) {
            'Oracle'     => 'SELECT '
                            . $platform->quoteIdentifier($this->sequenceName)
                            . '.NEXTVAL as "nextval" FROM dual',
            'PostgreSQL' => 'SELECT NEXTVAL(\'"' . $this->sequenceName . '"\')',
            default      => throw new RuntimeException('Unsupported platform for retrieving next sequence id'),
        };

        $statement = $this->tableGateway->adapter->createStatement();
        $statement->prepare($sql);
        $result   = $statement->execute();
        $sequence = $result->current();
        unset($statement, $result);
        return $sequence['nextval'];
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     *
     * @throws RuntimeException
     */
    public function lastSequenceId(): int
    {
        $platform     = $this->tableGateway->adapter->getPlatform();
        $platformName = $platform->getName();

        // todo: Remove string usage
        $sql = match ($platformName) {
            'Oracle'     => 'SELECT '
                            . $platform->quoteIdentifier($this->sequenceName)
                            . '.CURRVAL as "currval" FROM dual',
            'PostgreSQL' => 'SELECT LAST_INSERT_ROWID() as "currval"',
            default => throw new RuntimeException('Unsupported platform for retrieving last sequence id'),
        };

        $statement = $this->tableGateway->adapter->createStatement();
        $statement->prepare($sql);
        $result   = $statement->execute();
        $sequence = $result->current();
        unset($statement, $result);
        return $sequence['currval'];
    }
}
