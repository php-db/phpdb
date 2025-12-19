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
use function str_replace;
use function strtolower;

class Platform extends AbstractPlatform
{
    protected PlatformInterface $defaultPlatform;

    protected ?string $cachedPlatformName = null;

    /**
     * @todo sat-migration
     * We have removed the default behaviour of setting a decorator for the adapter's platform.
     * $platformName                    = $this->resolvePlatformName($platform);
     * $this->decorators[$platformName] = $this->defaultPlatform->getSqlPlatformDecorator();
     *
     * The migration of the adapters means checking the below:-
     * $mySqlPlatform     = new Mysql\Mysql();
     * $sqlServerPlatform = new SqlServer\SqlServer();
     * $oraclePlatform    = new Oracle\Oracle();
     * $ibmDb2Platform    = new IbmDb2\IbmDb2();
     * $sqlitePlatform    = new Sqlite\Sqlite();
     * $this->decorators['mysql']     = $mySqlPlatform->getDecorators();
     * $this->decorators['sqlserver'] = $sqlServerPlatform->getDecorators();
     * $this->decorators['oracle']    = $oraclePlatform->getDecorators();
     * $this->decorators['ibmdb2']    = $ibmDb2Platform->getDecorators();
     * $this->decorators['sqlite']    = $sqlitePlatform->getDecorators();
     */
    public function __construct(PlatformInterface $platform)
    {
        $this->defaultPlatform = $platform;
    }

    public function setTypeDecorator(
        string $type,
        PlatformDecoratorInterface $decorator,
        AdapterInterface|PlatformInterface|null $adapterOrPlatform = null
    ): void {
        $platformName                           = $this->resolvePlatformName($adapterOrPlatform);
        $this->decorators[$platformName][$type] = $decorator;
    }

    public function getTypeDecorator(
        PreparableSqlInterface|SqlInterface $subject,
        AdapterInterface|PlatformInterface|null $adapterOrPlatform = null
    ): PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface {
        $platformName = $this->resolvePlatformName($adapterOrPlatform);

        if (! isset($this->decorators[$platformName])) {
            return $subject;
        }

        $subjectClass = $subject::class;
        if (isset($this->decorators[$platformName][$subjectClass])) {
            $decorator = $this->decorators[$platformName][$subjectClass];
            $decorator->setSubject($subject);
            return $decorator;
        }

        /** @var PlatformDecoratorInterface $decorator */
        foreach ($this->decorators[$platformName] as $type => $decorator) {
            if ($subject instanceof $type && is_a($decorator, $type, true)) {
                $decorator->setSubject($subject);
                return $decorator;
            }
        }

        return $subject;
    }

    public function getDecorators(): array
    {
        $platformName = $this->resolvePlatformName($this->getDefaultPlatform());
        return $this->decorators[$platformName];
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface {
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
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
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

    protected function resolvePlatformName(PlatformInterface|AdapterInterface|null $adapterOrPlatform): string
    {
        if ($adapterOrPlatform === null && $this->cachedPlatformName !== null) {
            return $this->cachedPlatformName;
        }

        $platformName = $this->resolvePlatform($adapterOrPlatform)->getName();
        $normalized   = str_replace([' ', '_'], '', strtolower($platformName));

        if ($adapterOrPlatform === null) {
            $this->cachedPlatformName = $normalized;
        }

        return $normalized;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    protected function resolvePlatform(PlatformInterface|AdapterInterface|null $adapterOrPlatform): PlatformInterface
    {
        if ($adapterOrPlatform === null) {
            return $this->getDefaultPlatform();
        }

        if ($adapterOrPlatform instanceof AdapterInterface) {
            return $adapterOrPlatform->getPlatform();
        }

        return $adapterOrPlatform;
    }

    protected function getDefaultPlatform(): PlatformInterface
    {
        return $this->defaultPlatform;
    }
}
