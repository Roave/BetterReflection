<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionObject;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflectionTest\Fixture\ClassForHinting;

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
        $this->assertSame('stdClass', $classInfo->getName());
        $this->assertTrue($classInfo->isInternal());
    }

    public function testReflectionWorksWithEvaledClasses()
    {
        $foo = new ClassForHinting();

        $classInfo = ReflectionObject::createFromInstance($foo);
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
}
