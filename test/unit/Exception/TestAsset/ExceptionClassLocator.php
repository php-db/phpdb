<?php

declare(strict_types=1);

namespace PhpDbTest\Exception\TestAsset;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

use function class_exists;
use function dirname;
use function interface_exists;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Finds the package's exception classes by scanning src/.
 *
 * Scanning rather than listing means a new exception class comes under the structural
 * guards as soon as it is written.
 */
final class ExceptionClassLocator
{
    /**
     * Every concrete class in a src Exception directory, keyed by short name.
     *
     * @return iterable<string, class-string>
     */
    public static function concreteClasses(): iterable
    {
        $srcDir =
            dirname(
                path: __DIR__,
                levels: 4,
            ) . '/src';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            if ('Exception' !== $file->getPathInfo()->getFilename()) {
                continue;
            }

            $class = self::classFor($file, $srcDir);
            if (interface_exists($class) || ! class_exists($class)) {
                continue;
            }

            yield $file->getBasename('.php') => $class;
        }
    }

    /**
     * The named constructors a class declares itself.
     *
     * @param class-string $class
     * @return list<string>
     */
    public static function namedConstructors(string $class): array
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
    public static function templateConstantFor(string $method): string
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

    /** @return class-string */
    private static function classFor(SplFileInfo $file, string $srcDir): string
    {
        $relative  = substr($file->getPathname(), strlen($srcDir) + 1);
        $className = substr(
            string: $relative,
            offset: 0,
            length: -4,
        );

        return 'PhpDb\\'
            . str_replace(
                search: '/',
                replace: '\\',
                subject: $className,
            );
    }
}
