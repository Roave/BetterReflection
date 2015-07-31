<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionObject;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflectionTest\Fixture\ClassForHinting;

/**
 * @covers \BetterReflection\Reflection\ReflectionObject
 */
class ReflectionObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionThrownWhenNonObjectGiven()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        ReflectionObject::createFromInstance(123);
    }

    public function testReflectionWorksWithInternalClasses()
    {
        $foo = new \stdClass();

        $classInfo = ReflectionObject::createFromInstance($foo);
        $this->assertInstanceOf(ReflectionObject::class, $classInfo);
        $this->assertSame('stdClass', $classInfo->getName());
        $this->assertTrue($classInfo->isInternal());
    }

    public function testReflectionWorksWithEvaledClasses()
    {
        $foo = new ClassForHinting();

        $classInfo = ReflectionObject::createFromInstance($foo);
        $this->assertInstanceOf(ReflectionObject::class, $classInfo);
        $this->assertSame(ClassForHinting::class, $classInfo->getName());
        $this->assertFalse($classInfo->isInternal());
    }

    public function testReflectionWorksWithDynamicallyDeclaredMembers()
    {
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);
        $propInfo = $classInfo->getProperty('bar');

        $this->assertInstanceOf(ReflectionProperty::class, $propInfo);
        $this->assertSame('bar', $propInfo->getName());
        $this->assertFalse($propInfo->isDefault());
    }

    public function testExceptionThrownWhenInvalidInstanceGiven()
    {
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        $mockClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionObjectReflection = new \ReflectionObject($classInfo);
        $reflectionObjectConstructorReflection = $reflectionObjectReflection->getConstructor();
        $reflectionObjectConstructorReflection->setAccessible(true);

        $this->setExpectedException(\InvalidArgumentException::class);
        $reflectionObjectConstructorReflection->invokeArgs($classInfo, [$mockClass, new \stdClass]);
    }

    public function reflectionClassMethodProvider()
    {
        $publicClassMethods = get_class_methods(ReflectionClass::class);

        $ignoreMethods = [
            'createFromName',
            'createFromNode',
        ];

        $filteredMethods = [];
        foreach ($publicClassMethods as $method) {
            if (!in_array($method, $ignoreMethods, true)) {
                $filteredMethods[$method] = [$method];
            }
        }

        return $filteredMethods;
    }

    /**
     * @param string $methodName
     * @dataProvider reflectionClassMethodProvider
     */
    public function testReflectionObjectOverridesAllMethodsInReflectionClass($methodName)
    {
        $publicObjectMethods = get_class_methods(ReflectionObject::class);

        $this->assertContains($methodName, $publicObjectMethods);

        $mockReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods([$methodName])
            ->getMock();
        $mockReflectionClass
            ->expects($this->atLeastOnce())
            ->method($methodName);

        $reflectionObject = ReflectionObject::createFromInstance(new \stdClass());

        $reflectionObjectReflection = new \ReflectionObject($reflectionObject);
        $reflectionObjectReflectionClassPropertyReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassPropertyReflection->setAccessible(true);
        $reflectionObjectReflectionClassPropertyReflection->setValue($reflectionObject, $mockReflectionClass);

        $reflectionObject->{$methodName}('foo', 'bar', 'baz');
    }
}
