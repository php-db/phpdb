<?php

declare(strict_types=1);

namespace PhpDbTest\Exception;

use Exception;
use InvalidArgumentException;
use PhpDb\Adapter\Exception as AdapterException;
use PhpDb\Exception as DbException;
use PhpDb\ResultSet\Exception as ResultSetException;
use PhpDb\RowGateway\Exception as RowGatewayException;
use PhpDb\Sql\Exception as SqlException;
use PhpDb\TableGateway\Exception as TableGatewayException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function array_keys;
use function is_subclass_of;
use function ksort;
use function preg_replace;
use function str_starts_with;
use function strtoupper;
use function substr;

/**
 * Structural guards over the exception namespaces.
 *
 * Every concrete exception extends its expected SPL parent and is reachable through the
 * package marker interface, and every named constructor has the template constant its
 * name implies.
 */
#[Group('unit')]
#[CoversNothing]
final class ExceptionHierarchyTest extends TestCase
{
    /**
     * Every concrete exception class in the package, mapped to the SPL class it must extend.
     *
     * A new exception class comes under the guards only once it is listed here.
     *
     * Both ErrorException classes map to \Exception rather than \ErrorException.
     * \ErrorException's third constructor argument is $severity where \Exception's is
     * $previous, so correcting the parent would change a released constructor contract.
     *
     * @var array<class-string<Throwable>, class-string<Throwable>>
     */
    private const array EXCEPTION_CLASSES = [
        AdapterException\ErrorException::class                       => Exception::class,
        AdapterException\InvalidArgumentException::class             => InvalidArgumentException::class,
        AdapterException\InvalidConnectionParametersException::class => RuntimeException::class,
        AdapterException\InvalidQueryException::class                => UnexpectedValueException::class,
        AdapterException\RuntimeException::class                     => RuntimeException::class,
        AdapterException\UnexpectedValueException::class             => UnexpectedValueException::class,
        AdapterException\VunerablePlatformQuoteException::class      => RuntimeException::class,
        DbException\ContainerException::class                        => RuntimeException::class,
        DbException\ErrorException::class                            => Exception::class,
        DbException\InvalidArgumentException::class                  => InvalidArgumentException::class,
        DbException\RuntimeException::class                          => RuntimeException::class,
        DbException\UnexpectedValueException::class                  => UnexpectedValueException::class,
        ResultSetException\InvalidArgumentException::class           => InvalidArgumentException::class,
        ResultSetException\RuntimeException::class                   => RuntimeException::class,
        RowGatewayException\InvalidArgumentException::class          => InvalidArgumentException::class,
        RowGatewayException\RuntimeException::class                  => RuntimeException::class,
        SqlException\InvalidArgumentException::class                 => InvalidArgumentException::class,
        SqlException\RuntimeException::class                         => RuntimeException::class,
        TableGatewayException\InvalidArgumentException::class        => InvalidArgumentException::class,
        TableGatewayException\RuntimeException::class                => RuntimeException::class,
    ];

    /**
     * Exempt from the naming guard: its factory is named for its arguments rather than the
     * fault, and holds its message inline.
     */
    private const string UNCONVERTED_CLASS = AdapterException\VunerablePlatformQuoteException::class;

    /** @return array<string, array{string, string}> */
    public static function exceptionClassProvider(): array
    {
        $cases = [];
        foreach (self::EXCEPTION_CLASSES as $class => $splParent) {
            $cases[$class] = [$class, $splParent];
        }

        ksort($cases);

        return $cases;
    }

    /** @return array<string, array{string, string, string}> */
    public static function namedConstructorProvider(): array
    {
        $cases = [];
        foreach (array_keys(self::EXCEPTION_CLASSES) as $class) {
            if (self::UNCONVERTED_CLASS === $class) {
                continue;
            }

            foreach (self::namedConstructors($class) as $method) {
                $cases["{$class}::{$method}()"] = [
                    $class,
                    $method,
                    self::templateConstantFor($method),
                ];
            }
        }

        ksort($cases);

        return $cases;
    }

    /**
     * The named constructors a class declares itself.
     *
     * @param class-string $class
     * @return list<string>
     */
    private static function namedConstructors(string $class): array
    {
        $methods = [];
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            $name = $method->getName();
            if ($method->getDeclaringClass()->getName() !== $class || ! str_starts_with($name, 'for')) {
                continue;
            }

            $methods[] = $name;
        }

        return $methods;
    }

    /**
     * The template constant a named constructor's name implies.
     */
    private static function templateConstantFor(string $method): string
    {
        $condition = substr(
            string: $method,
            offset: 3,
        );

        return strtoupper((string) preg_replace(
            pattern: '/(?<!^)[A-Z]/',
            replacement: '_$0',
            subject: $condition,
        ));
    }

    #[Test]
    #[DataProvider('exceptionClassProvider')]
    public function exceptionExtendsItsExpectedSplParent(string $class, string $splParent): void
    {
        self::assertTrue(
            is_subclass_of($class, $splParent),
            "{$class} does not extend {$splParent}",
        );
    }

    #[Test]
    #[DataProvider('exceptionClassProvider')]
    public function exceptionIsReachableThroughThePackageMarkerInterface(string $class): void
    {
        self::assertTrue(
            is_subclass_of($class, DbException\ExceptionInterface::class),
            "{$class} does not implement " . DbException\ExceptionInterface::class,
        );
    }

    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorHasATemplateConstantMirroringItsName(
        string $class,
        string $method,
        string $expectedConstant,
    ): void {
        self::assertArrayHasKey(
            $expectedConstant,
            (new ReflectionClass($class))->getConstants(),
            "{$class}::{$method}() has no {$expectedConstant} template constant",
        );
    }

    #[Test]
    public function theMarkerInterfaceIsAThrowableSubtype(): void
    {
        self::assertTrue(
            (new ReflectionClass(DbException\ExceptionInterface::class))->isSubclassOf(Throwable::class),
            DbException\ExceptionInterface::class . ' must extend Throwable, or it cannot be used as a type',
        );
    }
}
