<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\SingleFileSourceLocator;

class ClassReflectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \BetterReflection\Reflector\ClassReflector::getAllClasses()
     */
    public function testGetClassesFromFile()
    {
        $classes = (new ClassReflector(
            new SingleFileSourceLocator('test/Fixture/ExampleClass.php')
        ))->getAllClasses();

        $this->assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        $this->assertCount(3, $classes);
    }
}
