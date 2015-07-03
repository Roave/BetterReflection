<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\SingleFileSourceLocator;

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
        $this->assertCount(3, $classes);
    }
}
