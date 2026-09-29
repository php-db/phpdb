<?php

declare(strict_types=1);

namespace PhpDb\ResultSet\Exception;

use PhpDb\Exception;

use function sprintf;

final class InvalidArgumentException extends Exception\InvalidArgumentException
{
    final public const string UNRESOLVABLE_ITERATOR = 'The data source nests IteratorAggregate more than %d levels deep and cannot be resolved to an Iterator';

    final public const string NON_ITERATOR_DATA_SOURCE = 'The data source resolved to "%s", which is not an Iterator and cannot be traversed by a result set';

    public static function forNonIteratorDataSource(string $type): self
    {
        return new self(sprintf(self::NON_ITERATOR_DATA_SOURCE, $type));
    }

    public static function forUnresolvableIterator(int $maxDepth): self
    {
        return new self(sprintf(self::UNRESOLVABLE_ITERATOR, $maxDepth));
    }
}
