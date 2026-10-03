<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

/**
 * The buffering states a result set can be in.
 *
 * @internal
 */
enum RowBufferState
{
    /**
     * Nothing has been decided yet: buffer() may still turn buffering on, and the first
     * iteration will settle it as Disabled.
     */
    case Pending;

    /** Rows are held here as they are read. */
    case Storing;

    /**
     * The data source is its own buffer, either because it was an array or because a
     * ResultInterface reported itself buffered, so storing rows again would be waste.
     */
    case Passthrough;

    /** Iteration began before buffer() was called, so buffering can no longer start. */
    case Disabled;
}
