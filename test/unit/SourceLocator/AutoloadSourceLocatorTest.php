<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\AutoloadSourceLocator;
use BetterReflectionTest\Fixture\ClassForHinting;

/**
 * @covers \BetterReflection\SourceLocator\AutoloadSourceLocator
 */
class AutoloadSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testClassLoads()
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator());

        $className = 'BetterReflectionTest\Fixture\ExampleClass';
        $this->assertFalse(class_exists($className, false));
        $classInfo = $reflector->reflect($className);
        $this->assertFalse(class_exists($className, false));

        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassLoadsWorksWithExistingClass()
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator());

        // Ensure class is loaded first
        new ClassForHinting();
        $className = 'BetterReflectionTest\Fixture\ClassForHinting';
        $this->assertTrue(class_exists($className, false));

        $classInfo = $reflector->reflect($className);

        $this->assertSame('ClassForHinting', $classInfo->getShortName());
    }

    public function testExceptionThrownWhenInvalidTypeGiven()
    {
        $locator = new AutoloadSourceLocator();

        $type = new IdentifierType();
        $typeReflection = new \ReflectionObject($type);
        $prop = $typeReflection->getProperty('name');
        $prop->setAccessible(true);
        $prop->setValue($type, 'nonsense');

        $this->setExpectedException(\LogicException::class, 'AutoloadSourceLocator can only locate classes, you asked for: nonsense');
        $identifier = new Identifier('foo', $type);
        $locator->__invoke($identifier);
    }
}
