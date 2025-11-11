<?php

namespace PhpDb\Adapter\Platform;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Mysqli;
use PhpDb\Adapter\Driver\Pdo;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\InvalidArgumentException;

use Override;

use function implode;
use function str_replace;

class Mysql extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifier = ['`', '`'];

    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifierTo = '``';

    /** @var \mysqli|\PDO|Pdo\Pdo|Mysqli\Mysqli */
    protected $driver;

    /**
     * NOTE: Include dashes for MySQL only, need tests for others platforms
     *
     * @var string
     */
    protected $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_\-:])/i';

    /**
     * @param null|Mysqli\Mysqli|Pdo\Pdo|\mysqli|\PDO $driver
     */
    public function __construct($driver = null)
    {
        if ($driver) {
            $this->setDriver($driver);
        }
    }

    /**
     * @param Mysqli\Mysqli|Pdo\Pdo|\mysqli|\PDO $driver
     * @throws InvalidArgumentException
     *@return $this Provides a fluent interface
     */
    public function setDriver($driver)
    {
        // handle PhpDb drivers
        if (
            $driver instanceof Mysqli\Mysqli
            || ($driver instanceof Pdo\Pdo && $driver->getDatabasePlatformName() === 'Mysql')
            || $driver instanceof \mysqli
            || ($driver instanceof \PDO && $driver->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql')
        ) {
            $this->driver = $driver;
            return $this;
        }

        throw new Exception\InvalidArgumentException(
            '$driver must be a Mysqli or Mysql PDO PhpDb\Adapter\Driver, Mysqli instance or MySQL PDO instance'
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Override] public function getName()
    {
        return 'MySQL';
    }

    /**
     * {@inheritDoc}
     */
    #[Override] public function quoteIdentifierChain($identifierChain)
    {
        return '`' . implode('`.`', (array) str_replace('`', '``', $identifierChain)) . '`';
    }

    /**
     * {@inheritDoc}
     */
    #[Override] public function quoteValue($value)
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteValue($value);
    }

    /**
     * {@inheritDoc}
     */
    #[Override] public function quoteTrustedValue($value)
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteTrustedValue($value);
    }

    /**
     * @param  string $value
     * @return string|null
     */
    protected function quoteViaDriver($value)
    {
        if ($this->driver instanceof DriverInterface) {
            $resource = $this->driver->getConnection()->getResource();
        } else {
            $resource = $this->driver;
        }

        if ($resource instanceof \mysqli) {
            return '\'' . $resource->real_escape_string($value) . '\'';
        }
        if ($resource instanceof \PDO) {
            return $resource->quote($value);
        }

        return null;
    }
}
