<?php

namespace BetterReflectionTest\Reflection\Adapter;

use BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use ReflectionClass as CoreReflectionClass;
use ReflectionProperty as CoreReflectionProperty;
use BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;

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

    public function methodExpectationProvider()
    {
        $mockClassLike = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['getValue', NotImplemented::class, null, []],
            ['setValue', NotImplemented::class, null, [new \stdClass()]],
            ['isPublic', null, true, []],
            ['isPrivate', null, true, []],
            ['isProtected', null, true, []],
            ['isStatic', null, true, []],
            ['isDefault', null, true, []],
            ['getModifiers', null, 123, []],
            ['getDeclaringClass', null, $mockClassLike, []],
            ['getDocComment', null, '', []],
            ['setAccessible', NotImplemented::class, null, [true]],
        ];
    }

    /**
     * @param string $methodName
     * @param string|null $expectedException
     * @param mixed $returnValue
     * @param array $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods($methodName, $expectedException, $returnValue, array $args)
    {
        /* @var BetterReflectionProperty|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->getMockBuilder(BetterReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->setExpectedException($expectedException);
        }

        $adapter = new ReflectionPropertyAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport()
    {
        $this->setExpectedException(\Exception::class, 'Unable to export statically');
        ReflectionPropertyAdapter::export('foo', 0);
    }
}
