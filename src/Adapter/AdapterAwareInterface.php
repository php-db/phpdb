<?php

namespace Laminas\Db\Adapter;

interface AdapterAwareInterface
{
    /** Set db adapter */
    public function setDbAdapter(AdapterInterface $adapter): static;
}
