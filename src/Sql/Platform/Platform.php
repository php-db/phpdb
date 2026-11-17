<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use Override;
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

    /**
     * @return array<string, PlatformDecoratorInterface>
     */
    #[Override]
    public function getDecorators(): array
    {
        return $this->decoratorsFor($this->resolvePlatformName($this->getDefaultPlatform()));
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        $subject = $this->subject;
        if (! $subject instanceof SqlInterface) {
            throw Exception\RuntimeException::forSubjectNotImplementing(SqlInterface::class, 'getSqlString()');
        }

        $adapterPlatform = $this->resolvePlatform($adapterPlatform);

        return $this->requireDecoratorImplementing(
            $this->getTypeDecorator($subject, $adapterPlatform),
            SqlInterface::class,
            'getSqlString()',
        )->getSqlString($adapterPlatform);
    }

    #[Override]
    public function getTypeDecorator(
        PreparableSqlInterface|SqlInterface $subject,
        AdapterInterface|PlatformInterface|null $adapterOrPlatform = null,
    ): PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface {
        /** @var array<class-string, PlatformDecoratorInterface> $decorators */
        $decorators = $this->decoratorsFor($this->resolvePlatformName($adapterOrPlatform));

        $decorator = $decorators[$subject::class] ?? null;
        if (null !== $decorator) {
            $decorator->setSubject($subject);
            return $decorator;
        }

        foreach ($decorators as $type => $decorator) {
            if (! ($subject instanceof $type && is_a($decorator, $type, allow_string: true))) {
                continue;
            }

            $decorator->setSubject($subject);
            return $decorator;
        }

        return $subject;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer,
    ): StatementContainerInterface {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw Exception\RuntimeException::forSubjectNotImplementing(
                PreparableSqlInterface::class,
                'prepareStatement()',
            );
        }

        $this->requireDecoratorImplementing(
            $this->getTypeDecorator($this->subject, $adapter),
            PreparableSqlInterface::class,
            'prepareStatement()',
        )->prepareStatement($adapter, $statementContainer);

        return $statementContainer;
    }

    #[Override]
    public function setTypeDecorator(
        string $type,
        PlatformDecoratorInterface $decorator,
        AdapterInterface|PlatformInterface|null $adapterOrPlatform = null,
    ): void {
        $platformName = $this->resolvePlatformName($adapterOrPlatform);

        $decorators                      = $this->decoratorsFor($platformName);
        $decorators[$type]               = $decorator;
        $this->decorators[$platformName] = $decorators;
    }

    protected function getDefaultPlatform(): PlatformInterface
    {
        return $this->defaultPlatform;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    protected function resolvePlatform(PlatformInterface|AdapterInterface|null $adapterOrPlatform): PlatformInterface
    {
        if (null === $adapterOrPlatform) {
            return $this->getDefaultPlatform();
        }

        if ($adapterOrPlatform instanceof AdapterInterface) {
            return $adapterOrPlatform->getPlatform();
        }

        return $adapterOrPlatform;
    }

    protected function resolvePlatformName(PlatformInterface|AdapterInterface|null $adapterOrPlatform): string
    {
        if (null === $adapterOrPlatform && null !== $this->cachedPlatformName) {
            return $this->cachedPlatformName;
        }

        $platformName = $this->resolvePlatform($adapterOrPlatform)->getName();
        $normalized   = str_replace([' ', '_'], replace: '', subject: strtolower($platformName));

        if (null === $adapterOrPlatform) {
            $this->cachedPlatformName = $normalized;
        }

        return $normalized;
    }

    /**
     * The decorators registered for a platform, keyed by the subject type they decorate
     *
     * @return array<string, PlatformDecoratorInterface>
     */
    private function decoratorsFor(string $platformName): array
    {
        /** @var array<string, PlatformDecoratorInterface> */
        return $this->decorators[$platformName] ?? [];
    }
}
