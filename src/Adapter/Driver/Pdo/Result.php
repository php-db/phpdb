<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Pdo;

use Closure;
use Iterator;
use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use ReturnTypeWillChange;

use function in_array;
use function is_int;

class Result implements Iterator, ResultInterface
{
    public const STATEMENT_MODE_SCROLLABLE = 'scrollable';
    public const STATEMENT_MODE_FORWARD    = 'forward';

    protected string $statementMode = self::STATEMENT_MODE_FORWARD;

    /** @var int */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * @internal
     *
     * @var array
     */
    public const VALID_FETCH_MODES = [
        PDO::FETCH_LAZY, // 1
        PDO::FETCH_ASSOC, // 2
        PDO::FETCH_NUM, // 3
        PDO::FETCH_BOTH, // 4
        PDO::FETCH_OBJ, // 5
        PDO::FETCH_BOUND, // 6
        // \PDO::FETCH_COLUMN,  // 7 (not a valid fetch mode)
        PDO::FETCH_CLASS, // 8
        PDO::FETCH_INTO, // 9
        PDO::FETCH_FUNC, // 10
        PDO::FETCH_NAMED, // 11
        PDO::FETCH_KEY_PAIR, // 12
        PDO::FETCH_PROPS_LATE, // Extra option for \PDO::FETCH_CLASS
        // \PDO::FETCH_SERIALIZE, // Seems to have been removed
        // \PDO::FETCH_UNIQUE,    // Option for fetchAll
        PDO::FETCH_CLASSTYPE, // Extra option for \PDO::FETCH_CLASS
    ];

    /** @var PDOStatement */
    protected $resource;

    /** @var array Result options */
    protected $options;

    /**
     * Is the current complete?
     *
     * @var bool
     */
    protected $currentComplete = false;

    /**
     * Track current item in recordset
     *
     * @var mixed
     */
    protected $currentData;

    /**
     * Current position of scrollable statement
     *
     * @var int
     */
    protected $position = -1;

    /** @var string|int|false|null */
    protected $generatedValue;

    protected Closure|int|null $rowCount = null;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function buffer(): void {}

    /**
     * Count
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function count()
    {
        if (is_int($this->rowCount)) {
            return $this->rowCount;
        }
        if ($this->rowCount instanceof Closure) {
            $this->rowCount = (int) ($this->rowCount)();
        } else {
            $this->rowCount = (int) $this->resource->rowCount();
        }
        return $this->rowCount;
    }

    /**
     * Get the data
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function current()
    {
        if ($this->currentComplete) {
            return $this->currentData;
        }

        $this->currentData     = $this->resource->fetch($this->fetchMode);
        $this->currentComplete = true;
        return $this->currentData;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getAffectedRows(): int
    {
        return $this->resource->rowCount();
    }

    public function getFetchMode(): int
    {
        return $this->fetchMode;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFieldCount(): int
    {
        return $this->resource->columnCount();
    }

    #[Override]
    public function getGeneratedValue(): string|int|false|null
    {
        return $this->generatedValue;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function getQueryResult(?ResultSetInterface $resultPrototype = null): ResultSetInterface
    {
        if (! $this->isQueryResult()) {
            throw Exception\RuntimeException::forNonQueryResult();
        }

        $resultPrototype ??= new ResultSet();
        $resultSet       = clone $resultPrototype;
        $resultSet->initialize($this);

        return $resultSet;
    }

    /**
     * Get resource
     */
    #[Override]
    public function getResource(): mixed
    {
        return $this->resource;
    }

    public function getStatementMode(): string
    {
        return $this->statementMode;
    }

    /**
     * Initialize
     *
     * @param string|int|false|null $generatedValue
     */
    public function initialize(
        PDOStatement $resource,
        $generatedValue,
        Closure|int|null $rowCount = null,
    ): self {
        $this->resource       = $resource;
        $this->generatedValue = $generatedValue;
        $this->rowCount       = $rowCount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isBuffered(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isQueryResult(): bool
    {
        return $this->resource->columnCount() > 0;
    }

    /**
     * Key
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function key()
    {
        return $this->position;
    }

    /**
     * Next
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function next()
    {
        $this->currentData     = $this->resource->fetch($this->fetchMode);
        $this->currentComplete = true;
        $this->position++;
        return $this->currentData;
    }

    /**
     * @throws Exception\RuntimeException
     * @return void
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function rewind()
    {
        if (self::STATEMENT_MODE_FORWARD === $this->statementMode && $this->position > 0) {
            throw Exception\RuntimeException::forForwardOnlyRewind();
        }
        if (! $this->currentComplete) {
            $this->currentData     = $this->resource->fetch($this->fetchMode);
            $this->currentComplete = true;
        }
        $this->position = 0;
    }

    /**
     * @throws Exception\InvalidArgumentException On invalid fetch mode.
     */
    public function setFetchMode(int $fetchMode): void
    {
        if (! in_array($fetchMode, self::VALID_FETCH_MODES, true)) {
            throw Exception\InvalidArgumentException::forInvalidFetchMode();
        }

        $this->fetchMode = (int) $fetchMode;
    }

    public function setStatementMode(string $statementMode = self::STATEMENT_MODE_FORWARD): void
    {
        if (! in_array($statementMode, [self::STATEMENT_MODE_SCROLLABLE, self::STATEMENT_MODE_FORWARD], true)) {
            throw Exception\InvalidArgumentException::forInvalidStatementMode();
        }

        $this->statementMode = $statementMode;
    }

    /**
     * Valid
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function valid()
    {
        return false !== $this->currentData;
    }
}
