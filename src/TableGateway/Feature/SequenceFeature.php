<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\Insert;
use PhpDb\TableGateway\Exception\RuntimeException;

use function array_search;
use function is_array;
use function is_int;

/**
 * @api
 */
class SequenceFeature extends AbstractFeature
{
    protected string $primaryKeyField;

    protected string $sequenceName;

    protected ?int $sequenceValue = null;

    public function __construct(string $primaryKeyField, string $sequenceName)
    {
        $this->primaryKeyField = $primaryKeyField;
        $this->sequenceName    = $sequenceName;
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     *
     * @throws RuntimeException
     *
     * @mago-expect analysis:mixed-assignment
     */
    public function lastSequenceId(): int
    {
        $platform     = $this->tableGateway->adapter->getPlatform();
        $platformName = $platform->getName();

        // TODO(@simon-mundy): Remove string usage
        $sql = match ($platformName) {
            'Oracle'     => "SELECT {$platform->quoteIdentifier($this->sequenceName)}.CURRVAL as \"currval\" FROM dual",
            'PostgreSQL' => 'SELECT LAST_INSERT_ROWID() as "currval"',
            default      => throw RuntimeException::forUnsupportedLastSequencePlatform(),
        };

        $statement = $this->tableGateway->adapter->createStatement();
        $statement->prepare($sql);
        $result = $statement->execute();
        if (! $result instanceof ResultInterface) {
            throw RuntimeException::forMissingSequenceResult();
        }

        $sequence = $result->current();
        unset($statement, $result);

        if (! is_array($sequence) || ! is_int($sequence['currval'] ?? null)) {
            throw RuntimeException::forMissingCurrentSequenceValue();
        }

        return $sequence['currval'];
    }

    /**
     * Generate a new value from the specified sequence in the database, and return it.
     *
     * @throws RuntimeException
     *
     * @mago-expect analysis:mixed-assignment
     */
    public function nextSequenceId(): ?int
    {
        $platform     = $this->tableGateway->adapter->getPlatform();
        $platformName = $platform->getName();

        $sql = match ($platformName) {
            'Oracle'     => "SELECT {$platform->quoteIdentifier($this->sequenceName)}.NEXTVAL as \"nextval\" FROM dual",
            'PostgreSQL' => "SELECT NEXTVAL('\"{$this->sequenceName}\"')",
            default      => throw RuntimeException::forUnsupportedNextSequencePlatform(),
        };

        $statement = $this->tableGateway->adapter->createStatement();
        $statement->prepare($sql);
        $result = $statement->execute();
        if (! $result instanceof ResultInterface) {
            throw RuntimeException::forMissingSequenceResult();
        }

        $sequence = $result->current();
        unset($statement, $result);

        if (! is_array($sequence)) {
            throw RuntimeException::forMissingNextSequenceValue();
        }

        $nextValue = $sequence['nextval'] ?? null;

        return is_int($nextValue) ? $nextValue : null;
    }

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function postInsert(StatementInterface $statement, ResultInterface $result): void
    {
        if (null !== $this->sequenceValue) {
            $this->tableGateway->lastInsertValue = $this->sequenceValue;
        }
    }

    /**
     * @throws RuntimeException
     */
    public function preInsert(Insert $insert): Insert
    {
        $columns = $insert->getRawState('columns');
        $values  = $insert->getRawState('values');

        if (! is_array($columns) || ! is_array($values)) {
            throw RuntimeException::forNonArrayInsertData();
        }

        $key = array_search($this->primaryKeyField, $columns, strict: true);
        if (false !== $key) {
            $sequenceValue       = $values[$key] ?? null;
            $this->sequenceValue = is_int($sequenceValue) ? $sequenceValue : null;
            return $insert;
        }

        $this->sequenceValue = $this->nextSequenceId();
        if (null === $this->sequenceValue) {
            return $insert;
        }

        $insert->values([$this->primaryKeyField => $this->sequenceValue], Insert::VALUES_MERGE);
        return $insert;
    }
}
