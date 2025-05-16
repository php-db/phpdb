<?php

namespace Laminas\Db\Adapter;

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
