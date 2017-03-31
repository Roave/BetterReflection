<?php

namespace Roave\BetterReflectionTest\Reflector;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflector\ClassReflector
 */
class ClassReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClassesFromFile()
    {
        $classes = (new ClassReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php')
        ))->getAllClasses();

        self::assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        self::assertCount(8, $classes);
    }

    public function testReflectProxiesToSourceLocator()
    {
        $reflection = $this->createMock(ReflectionClass::class);

        /** @var StringSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(StringSourceLocator::class)
            ->setConstructorArgs(['<?php'])
            ->setMethods(['locateIdentifier'])
            ->getMock();

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue($reflection));

        $reflector = new ClassReflector($sourceLocator);

        self::assertSame($reflection, $reflector->reflect('MyClass'));
    }

    public function testBuildDefaultReflector()
    {
        $defaultReflector = ClassReflector::buildDefaultReflector();

        $sourceLocator = $this->getObjectAttribute($defaultReflector, 'sourceLocator');
        self::assertInstanceOf(AggregateSourceLocator::class, $sourceLocator);
    }

    public function testThrowsExceptionWhenIdentifierNotFound()
    {
        $defaultReflector = ClassReflector::buildDefaultReflector();

        $this->expectException(IdentifierNotFound::class);

        $defaultReflector->reflect('Something\That\Should\Not\Exist');
    }
}
