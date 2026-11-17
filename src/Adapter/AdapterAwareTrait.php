<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

/**
 * @api
 */
trait AdapterAwareTrait
{
    protected AdapterInterface $adapter;

    /** Set db adapter */
    public function setDbAdapter(AdapterInterface $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }
}
