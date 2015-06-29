<?php

namespace AsgrimTest;

use Asgrim\ReflectionClass;
use Asgrim\ReflectionProperty;
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

    public function testGetProperties()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $properties = $classInfo->getProperties();

        $this->assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);
        $this->assertCount(4, $properties);
    }

    public function testGetProperty()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty('publicProperty');

        $this->assertInstanceOf(ReflectionProperty::class, $property);
        $this->assertSame('publicProperty', $property->getName());
    }

    public function testGetFileName()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $detectedFilename = $classInfo->getFileName();

        $this->assertSame('ExampleClass.php', basename($detectedFilename));
    }

    public function testGetClassesFromFile()
    {
        $filename = 'test/Fixture/ExampleClass.php';
        $classes = $this->reflector->getClassesFromFile($filename);

        $this->assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        $this->assertCount(3, $classes);
    }

    public function typesDataProvider()
    {
        return [
            ['privateProperty', ['int', 'float', '\stdClass']],
            ['protectedProperty', ['bool', 'bool[]', 'bool[][]']],
            ['publicProperty', ['string']],
        ];
    }

    /**
     * @param string $propertyName
     * @param string[] $expectedTypes
     * @dataProvider typesDataProvider
     */
    public function testGetTypeStrings($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedTypes, $property->getTypeStrings());
    }
}
