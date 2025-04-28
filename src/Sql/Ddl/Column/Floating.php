<?php

namespace Laminas\Db\Sql\Ddl\Column;

/**
 * Column representing a FLOAT type.
 *
 * Cannot name a class "float" starting in PHP 7, as it's a reserved keyword;
 * hence, "floating", with a type of "FLOAT".
 */
final class Floating extends AbstractPrecisionColumn
{
    protected string $type = 'FLOAT';
}
