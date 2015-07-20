<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\Generic;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflector\ClassReflector
 */
class ClassReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClassesFromFile()
    {
        $classes = (new ClassReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php')
        ))->getAllClasses();

        $this->assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        $this->assertCount(8, $classes);
    }

    public function testReflectProxiesToGenericReflectMethod()
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php'));

        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $genericReflectorMock = $this->getMockBuilder(Generic::class)
            ->setMethods(['reflect'])
            ->disableOriginalConstructor()
            ->getMock();

        $genericReflectorMock->expects($this->once())
            ->method('reflect')
            ->will($this->returnValue($reflectionMock));

        $reflectorReflection = new \ReflectionObject($reflector);
        $reflectorReflectorReflection = $reflectorReflection->getProperty('reflector');
        $reflectorReflectorReflection->setAccessible(true);
        $reflectorReflectorReflection->setValue($reflector, $genericReflectorMock);

        $reflector->reflect('MyClass');
    }
}
