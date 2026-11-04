<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Select;
use Stringable;

/**
 * Renders LIMIT and OFFSET the way the phpdb-mysql and phpdb-sqlite SelectDecorators do when
 * the statement is not prepared: the raw value is returned as the clause parameter, without
 * being cast to a string.
 *
 * @psalm-type RawValue = int|float|string|Stringable|list<string>|null
 */
final class UnpreparedScalarLimitSelect extends Select
{
    /**
     * @param RawValue $rawLimit
     * @param RawValue $rawOffset
     * @param array<string, list<array<int|string, string>|null>>|null $limitSpecification
     */
    public function __construct(
        string $table,
        private readonly int|float|string|Stringable|array|null $rawLimit,
        private readonly int|float|string|Stringable|array|null $rawOffset = null,
        private readonly ?array $limitSpecification = null,
    ) {
        parent::__construct($table);

        if (null !== $this->limitSpecification) {
            $this->specifications[self::LIMIT] = $this->limitSpecification;
        }
    }

    #[Override]
    protected function localizeVariables(): void
    {
        parent::localizeVariables();

        if (null === $this->rawLimit && null !== $this->rawOffset) {
            $this->specifications[self::LIMIT] = 'LIMIT 18446744073709551615';
        }
    }

    /** @return array{0: RawValue}|null */
    #[Override]
    protected function processLimit(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->rawLimit && null !== $this->rawOffset) {
            return [''];
        }

        if (null === $this->rawLimit) {
            return null;
        }

        return [$this->rawLimit];
    }

    /** @return array{0: RawValue}|null */
    #[Override]
    protected function processOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->rawOffset) {
            return null;
        }

        return [$this->rawOffset];
    }
}
