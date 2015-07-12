<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\Exception\NoParent;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflection\ReflectionClass
 */
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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/NoNamespace.php'));
        $classInfo = $reflector->reflect('ClassWithNoNamespace');

        $this->assertFalse($classInfo->inNamespace());
        $this->assertSame('ClassWithNoNamespace', $classInfo->getName());
        $this->assertSame('', $classInfo->getNamespaceName());
        $this->assertSame('ClassWithNoNamespace', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithExplicitGlobalNamespace()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect('ClassWithExplicitGlobalNamespace');

        $this->assertFalse($classInfo->inNamespace());
        $this->assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getName());
        $this->assertSame('', $classInfo->getNamespaceName());
        $this->assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getShortName());
    }

    /**
     * @coversNothing
     */
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
        $this->assertNull($classInfo->getConstant('NON_EXISTENT_CONSTANT'));
    }

    public function testIsConstructor()
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

        $this->assertNull($classInfo->getProperty('aNonExistentProperty'));

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

    public function testStaticCreation()
    {
        $reflection = ReflectionClass::createFromName('BetterReflectionTest\Fixture\ExampleClass');
        $this->assertSame('ExampleClass', $reflection->getShortName());
    }

    public function testGetParentClassDefault()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $childReflection = $reflector->reflect('BetterReflectionTest\Fixture\ClassWithParent');

        $parentReflection = $childReflection->getParentClass();
        $this->assertSame('ExampleClass', $parentReflection->getShortName());
    }

    public function testGetParentClassThrowsExceptionWithNoParent()
    {
        $reflection = ReflectionClass::createFromName('BetterReflectionTest\Fixture\ExampleClass');

        $this->setExpectedException(NoParent::class);
        $reflection->getParentClass();
    }

    public function testGetParentClassWithSpecificSourceLocator()
    {
        $mockLocator = $this->getMockBuilder(SourceLocator::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $mockLocator
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnCallback(function ($identifier) {
                $realLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php');
                return $realLocator->__invoke($identifier);
            }));

        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $childReflection = $reflector->reflect('BetterReflectionTest\Fixture\ClassWithParent');

        $parentReflection = $childReflection->getParentClass($mockLocator);
        $this->assertSame('ExampleClass', $parentReflection->getShortName());
    }

    public function startEndLineProvider()
    {
        return [
            ["<?php\n\nclass Foo {\n}\n", 3, 4],
            ["<?php\n\nclass Foo {\n\n}\n", 3, 5],
            ["<?php\n\n\nclass Foo {\n}\n", 4, 5],
        ];
    }

    /**
     * @param string $php
     * @param int $expectedStart
     * @param int $expectedEnd
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine($php, $expectedStart, $expectedEnd)
    {
        $reflector = new ClassReflector(new StringSourceLocator($php));
        $classInfo = $reflector->reflect('Foo');

        $this->assertSame($expectedStart, $classInfo->getStartLine());
        $this->assertSame($expectedEnd, $classInfo->getEndLine());
    }

    public function testGetDocComment()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertContains('Some comments here', $classInfo->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect('\BetterReflectionTest\FixtureOther\AnotherClass');

        $this->assertSame('', $classInfo->getDocComment());
    }

    public function testHasProperty()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertFalse($classInfo->hasProperty('aNonExistentProperty'));
        $this->assertTrue($classInfo->hasProperty('publicProperty'));
    }

    public function testHasConstant()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertFalse($classInfo->hasConstant('NON_EXISTENT_CONSTANT'));
        $this->assertTrue($classInfo->hasConstant('MY_CONST_1'));
    }

    public function testHasMethod()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertFalse($classInfo->hasMethod('aNonExistentMethod'));
        $this->assertTrue($classInfo->hasMethod('someMethod'));
    }

    public function testGetDefaultProperties()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $defaultProperties = $classInfo->getDefaultProperties();

        $this->assertCount(3, $defaultProperties);
    }

    public function testIsInternal()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertFalse($classInfo->isInternal());
        $this->assertTrue($classInfo->isUserDefined());
    }

    public function testIsAbstract()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\AbstractClass');
        $this->assertTrue($classInfo->isAbstract());

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertFalse($classInfo->isAbstract());
    }

    public function testIsFinal()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\FinalClass');
        $this->assertTrue($classInfo->isFinal());

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertFalse($classInfo->isFinal());
    }

    public function modifierProvider()
    {
        return [
            ['ExampleClass', 0, []],
            ['AbstractClass', \ReflectionClass::IS_EXPLICIT_ABSTRACT, ['abstract']],
            ['FinalClass', \ReflectionClass::IS_FINAL, ['final']],
        ];
    }

    /**
     * @param string $className
     * @param int $expectedModifier
     * @param string[] $expectedModifierNames
     * @dataProvider modifierProvider
     */
    public function testGetModifiers($className, $expectedModifier, array $expectedModifierNames)
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\\' . $className);

        $this->assertSame($expectedModifier, $classInfo->getModifiers());
        $this->assertSame(
            $expectedModifierNames,
            \Reflection::getModifierNames($classInfo->getModifiers())
        );
    }

    public function testIsTrait()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleTrait');
        $this->assertTrue($classInfo->isTrait());

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertFalse($classInfo->isTrait());
    }
}
