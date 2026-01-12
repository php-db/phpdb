<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Feature;

use PhpDb\Feature\FeatureInterface as BaseFeatureInterface;
use PhpDb\RowGateway\AbstractRowGateway;

interface FeatureInterface extends BaseFeatureInterface
{
    public function setRowGateway(AbstractRowGateway $rowGateway): void;
}
