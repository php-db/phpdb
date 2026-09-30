<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;
use Traversable;

use function get_debug_type;
use function get_object_vars;
use function is_object;
use function iterator_to_array;

/**
 * A result set for fetch modes that yield objects, such as PDO::FETCH_OBJ.
 *
 * Rows arrive as the caller's own objects and leave as the same instances; nothing here
 * clones, hydrates or reshapes them. A row that is not an object means the fetch mode
 * and the result set disagree, so it is refused rather than cast.
 *
 * @api
 *
 * @extends AbstractResultSet<object>
 */
class ObjectResultSet extends AbstractResultSet
{
    /**
     * Iterator: get current item
     *
     * @throws Exception\RuntimeException
     * @throws Exception\ValueError If a row is not an object.
     */
    #[Override]
    public function current(): ?object
    {
        return $this->currentRow();
    }

    /**
     * Cast result set to array of arrays
     *
     * @throws Exception\ValueError If a row exposes nothing to cast.
     */
    #[Override]
    public function toArray(): array
    {
        $return = [];

        /** @var object $row Every row this set yields is an object, or mapRow() throws. */
        foreach ($this as $row) {
            $return[] = $this->rowToArray($row);
        }

        return $return;
    }

    /**
     * @throws Exception\ValueError If the row is not an object.
     */
    #[Override]
    protected function mapRow(mixed $row): object
    {
        if (is_object($row)) {
            return $row;
        }

        throw Exception\ValueError::forRowThatIsNotAnObject(get_debug_type($row), static::class);
    }

    /**
     * Read an object row's values for toArray(), which owes its caller arrays.
     *
     * @return array<array-key, mixed>
     *
     * @throws Exception\ValueError If the row exposes nothing to read.
     */
    private function rowToArray(object $row): array
    {
        if ($row instanceof Traversable) {
            return iterator_to_array($row);
        }

        $data = get_object_vars($row);

        if ([] === $data) {
            throw Exception\ValueError::forUnreadableRow(get_debug_type($row), static::class);
        }

        return $data;
    }
}
