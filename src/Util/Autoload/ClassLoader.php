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
     * @var bool
     */
    private $registered = false;

    /**
     * @var LoaderMethodInterface
     */
    private $loaderMethod;

    public function __construct(LoaderMethodInterface $loaderMethod)
    {
        $this->loaderMethod = $loaderMethod;
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return void
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function addClass(ReflectionClass $reflectionClass)
    {
        if (!$this->registered) {
            // @todo specific exception
            throw new \LogicException('You must call Autoload::initialise() before adding reflections to load');
        }

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
     * Register our own autoloader
     * @throws \LogicException
     * @return void
     */
    public function register()
    {
        if ($this->registered) {
            // @todo specific exception
            throw new \LogicException('Autoload::initialise has already been called');
        }

        spl_autoload_register([__CLASS__, 'autoload'], true, true);

        $this->registered = true;
    }

    /**
     * Reset our autoloader, unregistering it and returning it to a clean state
     * @return void
     */
    public function reset()
    {
        spl_autoload_unregister([$this, 'autoload']);
        $this->registered = false;
        $this->reflections = [];
    }

    /**
     * @param string $classToLoad
     * @return bool
     * @throws \RuntimeException
     */
    private function autoload($classToLoad)
    {
        if (!array_key_exists($classToLoad, $this->reflections)) {
            return false;
        }

        $classInfo = $this->reflections[$classToLoad];

        if (!class_exists($classToLoad, false)
            && !interface_exists($classToLoad, false)
            && !trait_exists($classToLoad, false)) {
            // @todo specific exception
            throw new \RuntimeException(sprintf('Unable to load class :( %s', $classToLoad));
        }

        return true;
    }
}
