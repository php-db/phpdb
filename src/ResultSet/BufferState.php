<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

/**
 * Represents the buffering state of a ResultSet
 */
enum BufferState
{
    /**
     * Default state - buffering not yet enabled, but can still be enabled
     * before iteration starts
     */
    case None;

    /**
     * Buffering is active - data is being stored in the buffer array
     */
    case Active;

    /**
     * Buffering was implicitly disabled because iteration started
     * without calling buffer() first
     */
    case Disabled;

    /**
     * The data source itself is already buffered (e.g., ArrayResult),
     * so no additional buffering is needed
     */
    case DataSourceBuffered;
}
