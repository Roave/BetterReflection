<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;

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

    public function testReflectProxiesToSourceLocator()
    {
        /** @var StringSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(StringSourceLocator::class)
            ->setConstructorArgs(['<?php'])
            ->setMethods(['locateIdentifier'])
            ->getMock();

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue('foo'));

        $reflector = new ClassReflector($sourceLocator);

        $this->assertSame('foo', $reflector->reflect('MyClass'));
    }

    public function testBuildDefaultReflector()
    {
        $defaultReflector = ClassReflector::buildDefaultReflector();

        $sourceLocator = $this->getObjectAttribute($defaultReflector, 'sourceLocator');
        $this->assertInstanceOf(AggregateSourceLocator::class, $sourceLocator);
    }

    public function testThrowsExceptionWhenIdentifierNotFound()
    {
        $defaultReflector = ClassReflector::buildDefaultReflector();

        $this->expectException(IdentifierNotFound::class);

        $defaultReflector->reflect('Something\That\Should\Not\Exist');
    }
}
