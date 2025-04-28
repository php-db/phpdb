<?php

namespace Laminas\Db\Sql\Platform\Oracle;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Platform\PlatformDecoratorInterface;
use Laminas\Db\Sql\Select;

use function array_shift;
use function array_unshift;
use function current;
use function strpos;

class SelectDecorator extends Select implements PlatformDecoratorInterface
{
    protected Select $subject;

    /**
     * @param Select $subject
     */
    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @see Select::renderTable
     *
     * @param string $table
     * @param null|string $alias
     */
    protected function renderTable($table, $alias = null): string
    {
        return $table . ($alias ? ' ' . $alias : '');
    }

    protected function localizeVariables(): void
    {
        parent::localizeVariables();
        unset($this->specifications[self::LIMIT]);
        unset($this->specifications[self::OFFSET]);

        $this->specifications['LIMITOFFSET'] = null;
    }

    protected function processLimitOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        array &$sqls = [],
        array $parameters = []
    ): void {
        if ($this->limit === null && $this->offset === null) {
            return;
        }

        $selectParameters = $parameters[self::SELECT];
        $starSuffix       = $platform->getIdentifierSeparator() . self::SQL_STAR;

        foreach ($selectParameters[0] as $i => $columnParameters) {
            if (
                $columnParameters[0] === self::SQL_STAR
                || (isset($columnParameters[1]) && $columnParameters[1] === self::SQL_STAR)
                || strpos($columnParameters[0], $starSuffix)
            ) {
                $selectParameters[0] = [[self::SQL_STAR]];
                break;
            }

            if (isset($columnParameters[1])) {
                array_shift($columnParameters);
                $selectParameters[0][$i] = $columnParameters;
            }
        }

        if ($this->offset === null) {
            $this->offset = 0;
        }

        // first, produce column list without compound names (using the AS portion only)
        array_unshift($sqls, $this->createSqlFromSpecificationAndParameters([
            'SELECT %1$s FROM (SELECT b.%1$s, rownum b_rownum FROM (' => current($this->specifications[self::SELECT]),
        ], $selectParameters));

        if ($parameterContainer) {
            $number = $this->processInfo['subselectCount'] ?: '';

            if ($this->limit === null) {
                $sqls[] = ') b ) WHERE b_rownum > (:offset' . $number . ')';
                $parameterContainer->offsetSet(
                    'offset' . $number,
                    $this->offset,
                    $parameterContainer::TYPE_INTEGER
                );
            } else {
                // create bottom part of query, with offset and limit using row_number
                $sqls[] = ') b WHERE rownum <= (:offset'
                    . $number
                    . '+:limit'
                    . $number
                    . ')) WHERE b_rownum >= (:offset'
                    . $number
                    . ' + 1)';

                $parameterContainer->offsetSet(
                    'offset' . $number,
                    $this->offset,
                    $parameterContainer::TYPE_INTEGER
                );

                $parameterContainer->offsetSet(
                    'limit' . $number,
                    $this->limit,
                    $parameterContainer::TYPE_INTEGER
                );
            }
            $this->processInfo['subselectCount']++;
        } else {
            if ($this->limit === null) {
                $sqls[] = ') b ) WHERE b_rownum > (' . (int) $this->offset . ')';
            } else {
                $sqls[] = ') b WHERE rownum <= ('
                    . (int) $this->offset
                    . '+'
                    . (int) $this->limit
                    . ')) WHERE b_rownum >= ('
                    . (int) $this->offset
                    . ' + 1)';
            }
        }

        $sqls[self::SELECT] = $this->createSqlFromSpecificationAndParameters(
            $this->specifications[self::SELECT],
            $parameters[self::SELECT]
        );
    }
}
