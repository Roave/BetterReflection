<?php

namespace BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionParameter as CoreReflectionParameter;
use BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionParameter
 */
class ReflectionParameterTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionParameterNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionParameter::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionParameterNamesProvider
     */
    public function testCoreReflectionParameters($methodName)
    {
        $reflectionParameterAdapterReflection = new CoreReflectionClass(ReflectionParameterAdapter::class);
        $this->assertTrue($reflectionParameterAdapterReflection->hasMethod($methodName));
    }
}
