<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\Exception\NotAnObject;
use BetterReflection\Reflection\Exception\NotAString;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;
use BetterReflectionTest\ClassWithInterfaces;
use BetterReflectionTest\ClassWithInterfacesOther;
use BetterReflectionTest\Fixture\ClassForHinting;

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

        $this->assertNull($reflection->getParentClass());
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

    public function testIsInterface()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleInterface');
        $this->assertTrue($classInfo->isInterface());

        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertFalse($classInfo->isInterface());
    }

    public function testGetTraits()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureA');
        $traits = $classInfo->getTraits($sourceLocator);

        $this->assertCount(1, $traits);
        $this->assertInstanceOf(ReflectionClass::class, $traits[0]);
        $this->assertTrue($traits[0]->isTrait());
    }

    public function testGetTraitsReturnsEmptyArrayWhenNoTraitsUsed()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureB');
        $traits = $classInfo->getTraits($sourceLocator);

        $this->assertCount(0, $traits);
    }

    public function testGetTraitNames()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');

        $this->assertSame(
            [
                'TraitFixtureTraitA',
            ],
            (new ClassReflector($sourceLocator))->reflect('TraitFixtureA')->getTraitNames($sourceLocator)
        );
    }

    public function testGetTraitAliases()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureC');

        $this->assertSame([
            'a_protected' => 'TraitFixtureTraitC::a',
            'b_renamed' => 'TraitFixtureTraitC::b',
        ], $classInfo->getTraitAliases());
    }

    public function testGetInterfaceNames()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');

        $this->assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                \E::class,
            ],
            (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaceNames($sourceLocator),
            'Interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfaces()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaces($sourceLocator);

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            \E::class,
        ];

        $this->assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            $this->assertArrayHasKey($expectedInterface, $interfaces);
            $this->assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            $this->assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfaceNamesWillReturnAllInheritedInterfaceImplementationsOnASubclass()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');

        $this->assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                \E::class,
            ],
            (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\SubExampleClass::class)
                ->getInterfaceNames($sourceLocator),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillReturnAllInheritedInterfaceImplementationsOnASubclass()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class)
            ->getInterfaces($sourceLocator);

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            \E::class,
        ];

        $this->assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            $this->assertArrayHasKey($expectedInterface, $interfaces);
            $this->assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            $this->assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfaceNamesWillConsiderMultipleInheritanceLevelsAndImplementsOrderOverrides()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');

        $this->assertSame(
            [

                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                \E::class,
                ClassWithInterfaces\B::class,
            ],
            (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
                ->getInterfaceNames($sourceLocator),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillConsiderMultipleInheritanceLevels()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
            ->getInterfaces($sourceLocator);

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            \E::class,
            ClassWithInterfaces\B::class,
        ];

        $this->assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            $this->assertArrayHasKey($expectedInterface, $interfaces);
            $this->assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            $this->assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testIsInstance()
    {
        // note: ClassForHinting is safe to type-check against, as it will actually be loaded at runtime
        $class = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassForHinting.php')))
            ->reflect(ClassForHinting::class);

        $this->assertFalse($class->isInstance(new \stdClass()));
        $this->assertFalse($class->isInstance($this));
        $this->assertTrue($class->isInstance(new ClassForHinting()));

        $this->setExpectedException(NotAnObject::class);

        $class->isInstance('foo');
    }

    public function testIsSubclassOf()
    {
        $sourceLocator   = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $subExampleClass = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class);

        $this->assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubExampleClass::class, $sourceLocator),
            'Not a subclass of itself'
        );
        $this->assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubSubExampleClass::class, $sourceLocator),
            'Not a subclass of a child class'
        );
        $this->assertFalse(
            $subExampleClass->isSubclassOf(\stdClass::class, $sourceLocator),
            'Not a subclass of a unrelated'
        );
        $this->assertTrue(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\ExampleClass::class, $sourceLocator),
            'A subclass of a parent class'
        );
        $this->assertTrue(
            $subExampleClass->isSubclassOf('\\' . ClassWithInterfaces\ExampleClass::class, $sourceLocator),
            'A subclass of a parent class (considering eventual backslashes upfront)'
        );

        $this->setExpectedException(NotAString::class);

        $subExampleClass->isSubclassOf($this, $sourceLocator);
    }

    public function testImplementsInterface()
    {
        $sourceLocator   = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $subExampleClass = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class);

        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\A::class, $sourceLocator));
        $this->assertFalse($subExampleClass->implementsInterface(ClassWithInterfaces\B::class, $sourceLocator));
        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\B::class, $sourceLocator));
        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\C::class, $sourceLocator));
        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\D::class, $sourceLocator));
        $this->assertTrue($subExampleClass->implementsInterface(\E::class, $sourceLocator));
        $this->assertFalse($subExampleClass->implementsInterface(\Iterator::class, $sourceLocator));

        $this->setExpectedException(NotAString::class);

        $subExampleClass->implementsInterface($this, $sourceLocator);
    }
}
