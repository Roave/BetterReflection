<?php

namespace AsgrimTest;

use Asgrim\ReflectionMethod;
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
        $this->assertGreaterThanOrEqual(1, $classInfo->getMethods());
    }

    public function testGetConstants()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');
        $this->assertSame([
            'MY_CONST_1' => 123,
            'MY_CONST_2' => 234,
        ], $classInfo->getConstants());
    }

    public function testGetConstant()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');
        $this->assertSame(123, $classInfo->getConstant('MY_CONST_1'));
        $this->assertSame(234, $classInfo->getConstant('MY_CONST_2'));
    }

    public function testGetConstructor()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');
        $constructor = $classInfo->getConstructor();

        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
        $this->assertTrue($constructor->isConstructor());
    }
}
