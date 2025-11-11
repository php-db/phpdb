<?php

namespace PhpDb\Adapter;

interface AdapterAwareInterface
{
    /**
     * Set db adapter
     *
     * @return AdapterAwareInterface
     */
    public function setDbAdapter(AdapterInterface $adapter);
}
