<?php

namespace BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionObject as CoreReflectionObject;
use BetterReflection\Reflection\Adapter\ReflectionObject as ReflectionObjectAdapter;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionObject
 */
class ReflectionObjectTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionMethodNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionObject::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods($methodName)
    {
        $reflectionObjectAdapterReflection = new CoreReflectionClass(ReflectionObjectAdapter::class);
        $this->assertTrue($reflectionObjectAdapterReflection->hasMethod($methodName));
    }
}
