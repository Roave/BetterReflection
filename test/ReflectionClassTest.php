<?php

namespace AsgrimTest;

use Asgrim\Reflector;

class ReflectionClassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function setUp()
    {
        global $loader;
        $this->reflector = new Reflector($loader);
    }

    public function testClassNameMethodsWithNamespace()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $this->assertTrue($classInfo->inNamespace());
        $this->assertSame('AsgrimTest\Fixture\ExampleClass', $classInfo->getName());
        $this->assertSame('AsgrimTest\Fixture', $classInfo->getNamespaceName());
        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithoutNamespace()
    {
        $classInfo = $this->reflector->reflectClassFromFile(
            'ClassWithNoNamespace',
            __DIR__ . '/Fixture/NoNamespace.php'
        );

        $this->assertFalse($classInfo->inNamespace());
        $this->assertSame('ClassWithNoNamespace', $classInfo->getName());
        $this->assertSame('', $classInfo->getNamespaceName());
        $this->assertSame('ClassWithNoNamespace', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithExplicitGlobalNamespace()
    {
        $classInfo = $this->reflector->reflectClassFromFile(
            'ClassWithExplicitGlobalNamespace',
            __DIR__ . '/Fixture/ExampleClass.php'
        );

        $this->assertFalse($classInfo->inNamespace());
        $this->assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getName());
        $this->assertSame('', $classInfo->getNamespaceName());
        $this->assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getShortName());
    }

    public function testReflectingAClassDoesNotLoadTheClass()
    {
        $class = 'AsgrimTest\Fixture\ExampleClass';

        $this->assertFalse(class_exists($class, false));

        $this->reflector->reflect($class);

        $this->assertFalse(class_exists($class, false));
    }

    public function testGetMethods()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');
        $this->assertCount(1, $classInfo->getMethods());
    }
}
