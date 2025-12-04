<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function is_array;
use function str_replace;
use function strtoupper;
use function trim;

/**
 * Combine SQL statement - allows combining multiple select statements into one
 */
class Combine extends AbstractPreparableSql
{
    final public const COLUMNS = 'columns';

    final public const COMBINE = 'combine';

    final public const COMBINE_UNION = 'union';

    final public const COMBINE_EXCEPT = 'except';

    final public const COMBINE_INTERSECT = 'intersect';

    /** @var string[] */
    protected array $specifications = [
        self::COMBINE => '%1$s (%2$s) ',
    ];

    /** @var array<array{select: Select, type: string, modifier: string}> */
    private array $combine = [];

    public function __construct(
        Select|array|null $select = null,
        string $type = self::COMBINE_UNION,
        string $modifier = ''
    ) {
        if ($select) {
            $this->combine($select, $type, $modifier);
        }
    }

    /**
     * Create combine clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function combine(Select|array $select, string $type = self::COMBINE_UNION, string $modifier = ''): static
    {
        if (is_array($select)) {
            foreach ($select as $combine) {
                if ($combine instanceof Select) {
                    $combine = [$combine];
                }

                $this->combine(
                    $combine[0],
                    $combine[1] ?? $type,
                    $combine[2] ?? $modifier
                );
            }

            return $this;
        }

        $this->combine[] = [
            'select'   => $select,
            'type'     => $type,
            'modifier' => $modifier,
        ];
        return $this;
    }

    /**
     * Create union clause
     */
    public function union(Select|array $select, string $modifier = ''): static
    {
        return $this->combine($select, self::COMBINE_UNION, $modifier);
    }

    /**
     * Create except clause
     */
    public function except(Select|array $select, string $modifier = ''): static
    {
        return $this->combine($select, self::COMBINE_EXCEPT, $modifier);
    }

    /**
     * Create intersect clause
     */
    public function intersect(Select|array $select, string $modifier = ''): static
    {
        return $this->combine($select, self::COMBINE_INTERSECT, $modifier);
    }

    /**
     * Build sql string
     */
    #[Override]
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        if (! $this->combine) {
            return '';
        }

        $sql = '';
        foreach ($this->combine as $i => $combine) {
            $type   = $i === 0
                ? ''
                : strtoupper(
                    $combine['modifier']
                        ? "{$combine['type']} {$combine['modifier']}"
                        : $combine['type']
                );
            $select = $this->processSubSelect($combine['select'], $platform, $driver, $parameterContainer);
            $sql   .= str_replace(
                ['%1$s', '%2$s'],
                [$type, $select],
                $this->specifications[self::COMBINE]
            );
        }

        return trim($sql, ' ');
    }

    public function alignColumns(): static
    {
        if (! $this->combine) {
            return $this;
        }

        $allColumns = [];
        foreach ($this->combine as $combine) {
            $allColumns = array_merge(
                $allColumns,
                $combine['select']->getRawState(self::COLUMNS)
            );
        }

        foreach ($this->combine as $combine) {
            $combineColumns = $combine['select']->getRawState(self::COLUMNS);
            $aligned        = [];
            foreach (array_keys($allColumns) as $alias) {
                $aligned[$alias] = $combineColumns[$alias] ?? new Predicate\Expression('NULL');
            }

            $combine['select']->columns($aligned, false);
        }

        return $this;
    }

    /**
     * Get raw state
     */
    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            self::COMBINE => $this->combine,
            self::COLUMNS => $this->combine
                                ? $this->combine[0]['select']->getRawState(self::COLUMNS)
                                : [],
        ];
        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }
}
