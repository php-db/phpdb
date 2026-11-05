<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

/**
 * @api
 */
interface AdapterAwareInterface
{
    /** Set db adapter */
    public function setDbAdapter(AdapterInterface $adapter): static;
}
