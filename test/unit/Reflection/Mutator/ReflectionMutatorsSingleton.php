<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutator;

use Roave\BetterReflection\Reflection\Mutator\ReflectionMutators;

abstract class ReflectionMutatorsSingleton
{
    /**
     * @var ReflectionMutators|null
     */
    private static $reflectionMutators;

    final private function __construct()
    {
    }

    public static function instance() : ReflectionMutators
    {
        return self::$reflectionMutators ?? self::$reflectionMutators = new ReflectionMutators();
    }
}
