<?php

namespace Laminas\Db\Adapter;

use Override;

trait AdapterAwareTrait
{
    /** @var AdapterInterface */
    protected $adapter;

    /**
     * Set db adapter
     *
     * @return $this Provides a fluent interface
     */
    public function setDbAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }
}
