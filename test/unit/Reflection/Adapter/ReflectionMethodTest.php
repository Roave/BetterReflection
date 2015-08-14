<?php

namespace BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionMethod as CoreReflectionMethod;
use BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionMethod
 */
class ReflectionMethodTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionMethodNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionMethod::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods($methodName)
    {
        $reflectionMethodAdapterReflection = new CoreReflectionClass(ReflectionMethodAdapter::class);
        $this->assertTrue($reflectionMethodAdapterReflection->hasMethod($methodName));
    }
}
