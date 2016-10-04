<?php

namespace Roave\BetterReflection\Util;

use Roave\BetterReflection\Reflection\ReflectionClass;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\PrettyPrinter\Standard as CodePrinter;

class Autoload
{
    /**
     * @var ReflectionClass[]
     */
    private static $reflections = [];

    /**
     * @var bool
     */
    private static $initialiseCalled = false;

    /**
     * @param ReflectionClass $reflectionClass
     * @return void
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public static function addClass(ReflectionClass $reflectionClass)
    {
        if (!self::$initialiseCalled) {
            // @todo specific exception
            throw new \LogicException('You must call Autoload::initialise() before adding reflections to load');
        }

        if (array_key_exists($reflectionClass->getName(), self::$reflections)) {
            // @todo specific exception
            throw new \LogicException(sprintf('Class %s already registered', $reflectionClass->getName()));
        }

        if (class_exists($reflectionClass->getName(), false)) {
            // @todo specific exception
            throw new \RuntimeException(sprintf('Class already loaded: %s', $reflectionClass->getName()));
        }

        self::$reflections[$reflectionClass->getName()] = $reflectionClass;
    }

    /**
     * Register our own autoloader
     * @throws \LogicException
     * @return void
     */
    public static function initialise()
    {
        if (self::$initialiseCalled) {
            // @todo specific exception
            throw new \LogicException('Autoload::initialise has already been called');
        }

        spl_autoload_register([__CLASS__, 'autoload'], true, true);

        self::$initialiseCalled = true;
    }

    /**
     * Reset our autoloader, unregistering it and returning it to a clean state
     * @return void
     */
    public static function reset()
    {
        spl_autoload_unregister([__CLASS__, 'autoload']);
        self::$initialiseCalled = false;
        self::$reflections = [];
    }

    /**
     * @param string $classToLoad
     * @return bool
     * @throws \RuntimeException
     *
     * @todo what about internal classes? evaled classes? likely we can't modify them...
     */
    private static function autoload($classToLoad)
    {
        if (!array_key_exists($classToLoad, self::$reflections)) {
            return false;
        }

        $classInfo = self::$reflections[$classToLoad];

        $nodes = [];

        if ($classInfo->inNamespace()) {
            $nodes[] = new Namespace_(new Name($classInfo->getNamespaceName()));
        }

        // @todo need to work out if we need to add `use` imports too...

        $nodes[] = $classInfo->getAst();

        eval((new CodePrinter())->prettyPrint($nodes));

        if (!class_exists($classToLoad, false)) {
            // @todo specific exception
            throw new \RuntimeException(sprintf('Unable to load class :( %s', $classToLoad));
        }

        return true;
    }
}
