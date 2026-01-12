<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Feature\FeatureInterface as BaseFeatureInterface;
use PhpDb\TableGateway\AbstractTableGateway;

interface FeatureInterface extends BaseFeatureInterface
{
    public function setTableGateway(AbstractTableGateway $tableGateway): void;
}
