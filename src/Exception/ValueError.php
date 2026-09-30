<?php

declare(strict_types=1);

namespace PhpDb\Exception;

use ValueError as GlobalValueError;

class ValueError extends GlobalValueError implements ExceptionInterface {}
