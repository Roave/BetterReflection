<?php

namespace Roave\BetterReflection\Util\Autoload;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;

class ClassLoader
{
    /**
     * @var ReflectionClass[]
     */
    private $reflections = [];

    /**
     * @var LoaderMethodInterface
     */
    private $loaderMethod;

    public function __construct(LoaderMethodInterface $loaderMethod)
    {
        $this->loaderMethod = $loaderMethod;
        spl_autoload_register($this, true, true);
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return void
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function addClass(ReflectionClass $reflectionClass)
    {
        if (array_key_exists($reflectionClass->getName(), $this->reflections)) {
            // @todo specific exception
            throw new \LogicException(sprintf('Class %s already registered', $reflectionClass->getName()));
        }

        if (class_exists($reflectionClass->getName(), false)) {
            // @todo specific exception
            throw new \RuntimeException(sprintf('Class already loaded: %s', $reflectionClass->getName()));
        }

        $this->reflections[$reflectionClass->getName()] = $reflectionClass;
    }

    /**
     * @param string $classToLoad
     * @return bool
     * @throws \RuntimeException
     */
    public function __invoke($classToLoad)
    {
        if (!array_key_exists($classToLoad, $this->reflections)) {
            return false;
        }

        $this->loaderMethod->__invoke($this->reflections[$classToLoad]);

        if (!class_exists($classToLoad, false)
            && !interface_exists($classToLoad, false)
            && !trait_exists($classToLoad, false)) {
            // @todo specific exception
            throw new \RuntimeException(sprintf('Unable to load class :( %s', $classToLoad));
        }

        return true;
    }
}
