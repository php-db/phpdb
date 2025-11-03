<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use Countable;
use Iterator;
use Override;

interface ResultInterface extends
    Countable,
    Iterator
{
    /**
     * Force buffering
     */
    public function buffer(): void;

    /**
     * Check if is buffered
     */
    public function isBuffered(): ?bool;

    /**
     * Is query result?
     */
    public function isQueryResult(): bool;

    /**
     * Get affected rows
     */
    public function getAffectedRows(): int;

    /**
     * Get generated value
     *
     * @return mixed|null
     */
    public function getGeneratedValue();

    /**
     * Get the resource
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Get field count
     */
    public function getFieldCount(): int;
}
