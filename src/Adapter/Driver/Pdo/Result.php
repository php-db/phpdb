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

    /** @var mixed */
    protected $generatedValue;

    protected Closure|int $rowCount;

    /**
     * Initialize
     *
     * @param mixed $generatedValue
     */
    public function initialize(
        PDOStatement $resource,
        $generatedValue,
        Closure|int $rowCount = 0
    ): ResultInterface&Result {
        $this->resource       = $resource;
        $this->generatedValue = $generatedValue;
        $this->rowCount       = $rowCount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function buffer(): void
    {
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
     * @throws Exception\InvalidArgumentException On invalid fetch mode.
     */
    public function setFetchMode(int $fetchMode): void
    {
        if (! in_array($fetchMode, self::VALID_FETCH_MODES, true)) {
            throw new Exception\InvalidArgumentException(
                'The fetch mode must be one of the PDO::FETCH_* constants.'
            );
        }

        $this->fetchMode = (int) $fetchMode;
    }

    public function getFetchMode(): int
    {
        return $this->fetchMode;
    }

    public function setStatementMode(string $statementMode = self::STATEMENT_MODE_FORWARD): void
    {
        if (! in_array($statementMode, [self::STATEMENT_MODE_SCROLLABLE, self::STATEMENT_MODE_FORWARD], true)) {
            throw new Exception\InvalidArgumentException(
                'The statement mode must be one of the defined constants.'
            );
        }

        $this->statementMode = $statementMode;
    }

    public function getStatementMode(): string
    {
        return $this->statementMode;
    }

    /**
     * Get resource
     */
    #[Override]
    public function getResource(): mixed
    {
        return $this->resource;
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
     * @throws Exception\RuntimeException
     * @return void
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function rewind()
    {
        if ($this->statementMode === self::STATEMENT_MODE_FORWARD && $this->position > 0) {
            throw new Exception\RuntimeException(
                'This result is a forward only result set, calling rewind() after moving forward is not supported'
            );
        }
        if (! $this->currentComplete) {
            $this->currentData     = $this->resource->fetch($this->fetchMode);
            $this->currentComplete = true;
        }
        $this->position = 0;
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
        return $this->currentData !== false;
    }

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
        /** @phpstan-ignore instanceof.alwaysTrue */
        if ($this->rowCount instanceof Closure) {
            $this->rowCount = (int) ($this->rowCount)();
        } else {
            $this->rowCount = (int) $this->resource->rowCount();
        }
        return $this->rowCount;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFieldCount(): int
    {
        return $this->resource->columnCount();
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
     * {@inheritdoc}
     */
    #[Override]
    public function getAffectedRows(): int
    {
        return $this->resource->rowCount();
    }

    #[Override]
    public function getGeneratedValue(): mixed
    {
        return $this->generatedValue;
    }
}
