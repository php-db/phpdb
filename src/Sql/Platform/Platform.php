<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

use function is_a;
use function sprintf;
use function str_replace;
use function strtolower;

class Platform extends AbstractPlatform
{
    /** @var AdapterInterface */
    protected $adapter;

    /** @var PlatformInterface */
    protected $defaultPlatform;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter         = $adapter;
        $this->defaultPlatform = $adapter->getPlatform();
        // Note: SQL platform decorators initialization removed during refactoring
        // Decorators can be set manually via setTypeDecorator() if needed

        /**
         * todo: sat-migration
         * The following is deprecated and will be removed during cleanup
         */
        // $mySqlPlatform     = new Mysql\Mysql();
        // $sqlServerPlatform = new SqlServer\SqlServer();
        // $oraclePlatform    = new Oracle\Oracle();
        // $ibmDb2Platform    = new IbmDb2\IbmDb2();
        // $sqlitePlatform    = new Sqlite\Sqlite();

        // $this->decorators['mysql']     = $mySqlPlatform->getDecorators();
        // $this->decorators['sqlserver'] = $sqlServerPlatform->getDecorators();
        // $this->decorators['oracle']    = $oraclePlatform->getDecorators();
        // $this->decorators['ibmdb2']    = $ibmDb2Platform->getDecorators();
        // $this->decorators['sqlite']    = $sqlitePlatform->getDecorators();
    }

    /**
     * @param string                             $type
     * @param AdapterInterface|PlatformInterface $adapterOrPlatform
     */
    public function setTypeDecorator($type, PlatformDecoratorInterface $decorator, $adapterOrPlatform = null)
    {
        $platformName                           = $this->resolvePlatformName($adapterOrPlatform);
        $this->decorators[$platformName][$type] = $decorator;
    }

    /**
     * @param PreparableSqlInterface|SqlInterface     $subject
     * @param AdapterInterface|PlatformInterface|null $adapterOrPlatform
     * @return PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface
     */
    public function getTypeDecorator($subject, $adapterOrPlatform = null)
    {
        $platformName = $this->resolvePlatformName($adapterOrPlatform);

        if (isset($this->decorators[$platformName])) {
            foreach ($this->decorators[$platformName] as $type => $decorator) {
                if ($subject instanceof $type && is_a($decorator, $type, true)) {
                    $decorator->setSubject($subject);
                    return $decorator;
                }
            }
        }

        return $subject;
    }

    /**
     * @return PlatformDecoratorInterface
     */
    public function getDecorators()
    {
        $platformName = $this->resolvePlatformName($this->getDefaultPlatform());
        return $this->decorators[$platformName];
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer)
    {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\PreparableSqlInterface, thus calling '
                . 'prepareStatement() has no effect'
            );
        }

        $this->getTypeDecorator($this->subject, $adapter)->prepareStatement($adapter, $statementContainer);

        return $statementContainer;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null)
    {
        if (! $this->subject instanceof SqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\SqlInterface, thus calling '
                . 'prepareStatement() has no effect'
            );
        }

        $adapterPlatform = $this->resolvePlatform($adapterPlatform);

        return $this->getTypeDecorator($this->subject, $adapterPlatform)->getSqlString($adapterPlatform);
    }

    /**
     * @param AdapterInterface|PlatformInterface $adapterOrPlatform
     * @return string
     */
    protected function resolvePlatformName($adapterOrPlatform)
    {
        $platformName = $this->resolvePlatform($adapterOrPlatform)->getName();
        return str_replace([' ', '_'], '', strtolower($platformName));
    }

    /**
     * @param null|PlatformInterface|AdapterInterface $adapterOrPlatform
     * @return PlatformInterface
     * @throws Exception\InvalidArgumentException
     */
    protected function resolvePlatform($adapterOrPlatform)
    {
        if (! $adapterOrPlatform) {
            return $this->getDefaultPlatform();
        }

        if ($adapterOrPlatform instanceof AdapterInterface) {
            return $adapterOrPlatform->getPlatform();
        }

        if ($adapterOrPlatform instanceof PlatformInterface) {
            return $adapterOrPlatform;
        }

        throw new Exception\InvalidArgumentException(sprintf(
            '$adapterOrPlatform should be null, %s, or %s',
            AdapterInterface::class,
            PlatformInterface::class
        ));
    }

    /**
     * @return PlatformInterface
     * @throws Exception\RuntimeException
     */
    protected function getDefaultPlatform()
    {
        if (! $this->defaultPlatform) {
            throw new Exception\RuntimeException('$this->defaultPlatform was not set');
        }

        return $this->defaultPlatform;
    }
}
