<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use Roave\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use Roave\BetterReflection\Util\ClassExistenceChecker;

use function array_key_exists;
use function spl_autoload_register;

/**
 * @deprecated
 *
 * @psalm-suppress DeprecatedClass
 */
final class ClassLoader
{
    /** @var array<class-string, ReflectionClass> */
    private array $reflections = [];

    public function __construct(private LoaderMethodInterface $loaderMethod)
    {
        /** @phpstan-ignore-next-line */
        spl_autoload_register($this, true, true);
    }

    /**
     * @throws ClassAlreadyLoaded
     * @throws ClassAlreadyRegistered
     */
    public function addClass(ReflectionClass $reflectionClass): void
    {
        if (array_key_exists($reflectionClass->getName(), $this->reflections)) {
            throw Exception\ClassAlreadyRegistered::fromReflectionClass($reflectionClass);
        }

        if (ClassExistenceChecker::exists($reflectionClass->getName())) {
            throw Exception\ClassAlreadyLoaded::fromReflectionClass($reflectionClass);
        }

        $this->reflections[$reflectionClass->getName()] = $reflectionClass;
    }

    /** @throws FailedToLoadClass */
    public function __invoke(string $classToLoad): bool
    {
        if (! array_key_exists($classToLoad, $this->reflections)) {
            return false;
        }

        $this->loaderMethod->__invoke($this->reflections[$classToLoad]);

        if (! ClassExistenceChecker::exists($classToLoad)) {
            throw Exception\FailedToLoadClass::fromClassName($classToLoad);
        }

        return true;
    }
}
