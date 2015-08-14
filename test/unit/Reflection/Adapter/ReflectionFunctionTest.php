<?php

namespace BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use BetterReflection\Reflection\Adapter\ReflectionFunction as ReflectionFunctionAdapter;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionFunction
 */
class ReflectionFunctionTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionMethodNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionFunction::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods($methodName)
    {
        $reflectionFunctionAdapterReflection = new CoreReflectionClass(ReflectionFunctionAdapter::class);
        $this->assertTrue($reflectionFunctionAdapterReflection->hasMethod($methodName));
    }
}
