<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Predicate\PredicateSet;

use function hash;
use function is_array;
use function is_object;
use function json_encode;
use function method_exists;

use const JSON_THROW_ON_ERROR;

class CacheKeyGenerator
{
    private const CACHE_KEY_PREFIX = 'phpdb_sql_';

    public function generate(RawStateInterface $sql, PlatformInterface $platform): string
    {
        $rawState = $sql->getRawState();

        $normalizedState = $this->normalizeState($rawState);

        $data = [
            'platform' => $platform->getName(),
            'state'    => $normalizedState,
        ];

        $hash = hash('xxh128', json_encode($data, JSON_THROW_ON_ERROR));

        return self::CACHE_KEY_PREFIX . $hash;
    }

    private function normalizeState(mixed $state): mixed
    {
        if ($state === null) {
            return null;
        }

        if (is_array($state)) {
            $normalized = [];
            foreach ($state as $key => $value) {
                $normalized[$key] = $this->normalizeState($value);
            }
            return $normalized;
        }

        if (! is_object($state)) {
            return $state;
        }

        if ($state instanceof TableIdentifier) {
            return [
                '__type' => 'TableIdentifier',
                'table'  => $state->getTable(),
                'schema' => $state->getSchema(),
            ];
        }

        if ($state instanceof Join) {
            return [
                '__type' => 'Join',
                'joins'  => $this->normalizeState($state->getJoins()),
            ];
        }

        if ($state instanceof PredicateSet) {
            return [
                '__type'     => 'PredicateSet',
                'predicates' => $this->normalizePredicates($state->getPredicates()),
            ];
        }

        if ($state instanceof ExpressionInterface) {
            $data = $state->getExpressionData();
            return [
                '__type' => 'Expression',
                'spec'   => $data['spec'],
                'values' => $this->normalizeArgumentList($data['values']),
            ];
        }

        if ($state instanceof Select) {
            return [
                '__type' => 'Select',
                'state'  => $this->normalizeState($state->getRawState()),
            ];
        }

        if (method_exists($state, '__toString')) {
            return (string) $state;
        }

        return '__object__';
    }

    private function normalizePredicates(array $predicates): array
    {
        $normalized = [];
        foreach ($predicates as [$operator, $predicate]) {
            $normalized[] = [
                'op'        => $operator,
                'predicate' => $this->normalizeState($predicate),
            ];
        }
        return $normalized;
    }

    private function normalizeArgumentList(array $arguments): array
    {
        $normalized = [];
        foreach ($arguments as $argument) {
            if ($argument instanceof ArgumentInterface) {
                $normalized[] = [
                    '__type' => $argument::class,
                    'value'  => $this->normalizeState($argument->getValue()),
                ];
            } else {
                $normalized[] = $this->normalizeState($argument);
            }
        }
        return $normalized;
    }
}
