<?php

namespace PhpDb\Sql\Ddl\Column;

use PhpDb\Sql\ExpressionInterface;

/**
 * Interface ColumnInterface describes the protocol on how Column objects interact
 */
interface ColumnInterface extends ExpressionInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isNullable();

    /**
     * @return null|string|int
     */
    public function getDefault();

    /**
     * @return array
     */
    public function getOptions();
}
