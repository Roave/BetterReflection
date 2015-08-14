<?php

namespace BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionProperty as CoreReflectionProperty;
use BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionProperty
 */
class ReflectionPropertyTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionPropertyNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionProperty::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionPropertyNamesProvider
     */
    public function testCoreReflectionPropertys($methodName)
    {
        $reflectionPropertyAdapterReflection = new CoreReflectionClass(ReflectionPropertyAdapter::class);
        $this->assertTrue($reflectionPropertyAdapterReflection->hasMethod($methodName));
    }
}
