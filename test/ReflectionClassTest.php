<?php

namespace BetterReflectionTest;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflection\Symbol;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use BetterReflection\SourceLocator\SingleFileSourceLocator;

class ReflectionClassTest extends \PHPUnit_Framework_TestCase
{
    private function getComposerLocator()
    {
        global $loader;
        return new ComposerSourceLocator($loader);
    }

    public function testClassNameMethodsWithNamespace()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertTrue($classInfo->inNamespace());
        $this->assertSame('BetterReflectionTest\Fixture\ExampleClass', $classInfo->getName());
        $this->assertSame('BetterReflectionTest\Fixture', $classInfo->getNamespaceName());
        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithoutNamespace()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/Fixture/NoNamespace.php'));
        $classInfo = $reflector->reflect('ClassWithNoNamespace');

        $this->assertFalse($classInfo->inNamespace());
        $this->assertSame('ClassWithNoNamespace', $classInfo->getName());
        $this->assertSame('', $classInfo->getNamespaceName());
        $this->assertSame('ClassWithNoNamespace', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithExplicitGlobalNamespace()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect('ClassWithExplicitGlobalNamespace');

        $this->assertFalse($classInfo->inNamespace());
        $this->assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getName());
        $this->assertSame('', $classInfo->getNamespaceName());
        $this->assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getShortName());
    }

    public function testReflectingAClassDoesNotLoadTheClass()
    {
        $class = 'BetterReflectionTest\Fixture\ExampleClass';

        $this->assertFalse(class_exists($class, false));

        $reflector = new ClassReflector($this->getComposerLocator());
        $reflector->reflect($class);

        $this->assertFalse(class_exists($class, false));
    }

    public function testGetMethods()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertGreaterThanOrEqual(1, $classInfo->getMethods());
    }

    public function testGetConstants()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertSame([
            'MY_CONST_1' => 123,
            'MY_CONST_2' => 234,
        ], $classInfo->getConstants());
    }

    public function testGetConstant()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertSame(123, $classInfo->getConstant('MY_CONST_1'));
        $this->assertSame(234, $classInfo->getConstant('MY_CONST_2'));
    }

    public function testGetConstructor()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $constructor = $classInfo->getConstructor();

        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
        $this->assertTrue($constructor->isConstructor());
    }

    public function testGetProperties()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $properties = $classInfo->getProperties();

        $this->assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);
        $this->assertCount(4, $properties);
    }

    public function testGetProperty()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty('publicProperty');

        $this->assertInstanceOf(ReflectionProperty::class, $property);
        $this->assertSame('publicProperty', $property->getName());
    }

    public function testGetFileName()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $detectedFilename = $classInfo->getFileName();

        $this->assertSame('ExampleClass.php', basename($detectedFilename));
    }

    public function testGetClassesFromFile()
    {
        $filename = 'test/Fixture/ExampleClass.php';
        $singleFileSourceLocator = new SingleFileSourceLocator($filename);

        $reflector = new ClassReflector($singleFileSourceLocator);
        $classes = $reflector->getAllSymbols(Symbol::SYMBOL_CLASS);

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
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedTypes, $property->getTypeStrings());
    }
}
