<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Exception\NotAClassReflection;
use BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use BetterReflection\Reflection\Exception\NotAnObject;
use BetterReflection\Reflection\Exception\NotAString;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;
use BetterReflectionTest\ClassesImplementingIterators;
use BetterReflectionTest\ClassesWithCloneMethod;
use BetterReflectionTest\ClassWithInterfaces;
use BetterReflectionTest\ClassWithInterfacesExtendingInterfaces;
use BetterReflectionTest\ClassWithInterfacesOther;
use BetterReflectionTest\Fixture;
use BetterReflectionTest\Fixture\ClassForHinting;
use BetterReflectionTest\Fixture\InvalidInheritances;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;

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

    public function testCanReflectInternalClassWithDefaultLocator()
    {
        $this->assertSame(\stdClass::class, ReflectionClass::createFromName(\stdClass::class)->getName());
    }

    public function testCanReflectEvaledClassWithDefaultLocator()
    {
        $className = uniqid('foo');

        eval('class ' . $className . '{}');

        $this->assertSame($className, ReflectionClass::createFromName($className)->getName());
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

    public function testGetMethodsReturnsInheritedMethods()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassMethods.php'));
        $classInfo = $reflector->reflect('Qux');

        $methods = $classInfo->getMethods();
        $this->assertCount(6, $methods);
        $this->assertContainsOnlyInstancesOf(ReflectionMethod::class, $methods);

        $this->assertSame('a', $classInfo->getMethod('a')->getName(), 'Failed asserting that method a from interface Foo was returned');
        $this->assertSame('Foo', $classInfo->getMethod('a')->getDeclaringClass()->getName());

        $this->assertSame('b', $classInfo->getMethod('b')->getName(), 'Failed asserting that method b from trait Bar was returned');
        $this->assertSame('Bar', $classInfo->getMethod('b')->getDeclaringClass()->getName());

        $this->assertSame('c', $classInfo->getMethod('c')->getName(), 'Failed asserting that public method c from parent class Baz was returned');
        $this->assertSame('Baz', $classInfo->getMethod('c')->getDeclaringClass()->getName());

        $this->assertSame('d', $classInfo->getMethod('d')->getName(), 'Failed asserting that protected method d from parent class Baz was returned');
        $this->assertSame('Baz', $classInfo->getMethod('d')->getDeclaringClass()->getName());

        $this->assertSame('e', $classInfo->getMethod('e')->getName(), 'Failed asserting that private method e from parent class Baz was returned');
        $this->assertSame('Baz', $classInfo->getMethod('e')->getDeclaringClass()->getName());

        $this->assertSame('f', $classInfo->getMethod('f')->getName(), 'Failed asserting that method from SUT was returned');
        $this->assertSame('Qux', $classInfo->getMethod('f')->getDeclaringClass()->getName());
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
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php')))->reflect('Foo');

        $this->assertSame([
            'hasDefault' => 123,
            'noDefault' => null,
        ], $classInfo->getDefaultProperties());
    }

    public function testIsInternalWithUserDefinedClass()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertFalse($classInfo->isInternal());
        $this->assertTrue($classInfo->isUserDefined());
    }

    public function testIsInternalWithInternalClass()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect('stdClass');

        $this->assertTrue($classInfo->isInternal());
        $this->assertFalse($classInfo->isUserDefined());
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
        $traits = $classInfo->getTraits();

        $this->assertCount(1, $traits);
        $this->assertInstanceOf(ReflectionClass::class, $traits[0]);
        $this->assertTrue($traits[0]->isTrait());
    }

    public function testGetTraitsReturnsEmptyArrayWhenNoTraitsUsed()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureB');
        $traits = $classInfo->getTraits();

        $this->assertCount(0, $traits);
    }

    public function testGetTraitNames()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');

        $this->assertSame(
            [
                'TraitFixtureTraitA',
            ],
            (new ClassReflector($sourceLocator))->reflect('TraitFixtureA')->getTraitNames()
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
                ->getInterfaceNames(),
            'Interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfaces()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaces();

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
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillReturnAllInheritedInterfaceImplementationsOnASubclass()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class)
            ->getInterfaces();

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
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillConsiderMultipleInheritanceLevels()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
            ->getInterfaces();

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

    public function testGetInterfacesWillConsiderInterfaceInheritanceLevels()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\ExampleImplementingCompositeInterface::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfacesExtendingInterfaces\D::class,
            ClassWithInterfacesExtendingInterfaces\C::class,
            ClassWithInterfacesExtendingInterfaces\B::class,
            ClassWithInterfacesExtendingInterfaces\A::class,
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
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubExampleClass::class),
            'Not a subclass of itself'
        );
        $this->assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubSubExampleClass::class),
            'Not a subclass of a child class'
        );
        $this->assertFalse(
            $subExampleClass->isSubclassOf(\stdClass::class),
            'Not a subclass of a unrelated'
        );
        $this->assertTrue(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\ExampleClass::class),
            'A subclass of a parent class'
        );
        $this->assertTrue(
            $subExampleClass->isSubclassOf('\\' . ClassWithInterfaces\ExampleClass::class),
            'A subclass of a parent class (considering eventual backslashes upfront)'
        );

        $this->setExpectedException(NotAString::class);

        $subExampleClass->isSubclassOf($this);
    }

    public function testImplementsInterface()
    {
        $sourceLocator   = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $subExampleClass = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class);

        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\A::class));
        $this->assertFalse($subExampleClass->implementsInterface(ClassWithInterfaces\B::class));
        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\B::class));
        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\C::class));
        $this->assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\D::class));
        $this->assertTrue($subExampleClass->implementsInterface(\E::class));
        $this->assertFalse($subExampleClass->implementsInterface(\Iterator::class));

        $this->setExpectedException(NotAString::class);

        $subExampleClass->implementsInterface($this);
    }

    public function testIsInstantiable()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $this->assertTrue($reflector->reflect(Fixture\ExampleClass::class)->isInstantiable());
        $this->assertTrue($reflector->reflect(Fixture\ClassWithParent::class)->isInstantiable());
        $this->assertTrue($reflector->reflect(Fixture\FinalClass::class)->isInstantiable());
        $this->assertFalse($reflector->reflect(Fixture\ExampleTrait::class)->isInstantiable());
        $this->assertFalse($reflector->reflect(Fixture\AbstractClass::class)->isInstantiable());
        $this->assertFalse($reflector->reflect(Fixture\ExampleInterface::class)->isInstantiable());
    }

    public function testIsCloneable()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $this->assertTrue($reflector->reflect(Fixture\ExampleClass::class)->isCloneable());
        $this->assertTrue($reflector->reflect(Fixture\ClassWithParent::class)->isCloneable());
        $this->assertTrue($reflector->reflect(Fixture\FinalClass::class)->isCloneable());
        $this->assertFalse($reflector->reflect(Fixture\ExampleTrait::class)->isCloneable());
        $this->assertFalse($reflector->reflect(Fixture\AbstractClass::class)->isCloneable());
        $this->assertFalse($reflector->reflect(Fixture\ExampleInterface::class)->isCloneable());

        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesWithCloneMethod.php'
        ));

        $this->assertTrue($reflector->reflect(ClassesWithCloneMethod\WithPublicClone::class)->isCloneable());
        $this->assertFalse($reflector->reflect(ClassesWithCloneMethod\WithProtectedClone::class)->isCloneable());
        $this->assertFalse($reflector->reflect(ClassesWithCloneMethod\WithPrivateClone::class)->isCloneable());
    }

    public function testIsIterateable()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassesImplementingIterators.php');
        $reflector     = new ClassReflector($sourceLocator);

        $this->assertTrue(
            $reflector
                ->reflect(ClassesImplementingIterators\TraversableImplementation::class)
                ->isIterateable()
        );
        $this->assertFalse(
            $reflector
                ->reflect(ClassesImplementingIterators\NonTraversableImplementation::class)
                ->isIterateable()
        );
        $this->assertFalse(
            $reflector
                ->reflect(ClassesImplementingIterators\AbstractTraversableImplementation::class)
                ->isIterateable()
        );
        $this->assertFalse(
            $reflector
                ->reflect(ClassesImplementingIterators\TraversableExtension::class)
                ->isIterateable()
        );
    }

    public function testGetParentClassesFailsWithClassExtendingFromInterface()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\ClassExtendingInterface::class);

        $this->setExpectedException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetParentClassesFailsWithClassExtendingFromTrait()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\ClassExtendingTrait::class);

        $this->setExpectedException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromClass()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\InterfaceExtendingClass::class);

        $this->setExpectedException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromTrait()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\InterfaceExtendingTrait::class);

        $this->setExpectedException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetImmediateInterfaces()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PrototypeTree.php'));

        $interfaces = $reflector->reflect('Boom\B')->getImmediateInterfaces();

        $this->assertCount(1, $interfaces);
        $this->assertInstanceOf(ReflectionClass::class, $interfaces['Boom\Bar']);
        $this->assertSame('Boom\Bar', $interfaces['Boom\Bar']->getName());
    }

    public function testGetImmediateInterfacesDoesNotIncludeCurrentInterface()
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php'));

        $cInterfaces = array_map(
            function (ReflectionClass $interface) {
                return $interface->getShortName();
            },
            $reflector->reflect(ClassWithInterfacesExtendingInterfaces\C::class)->getImmediateInterfaces()
        );
        $dInterfaces = array_map(
            function (ReflectionClass $interface) {
                return $interface->getShortName();
            },
            $reflector->reflect(ClassWithInterfacesExtendingInterfaces\D::class)->getImmediateInterfaces()
        );

        sort($cInterfaces);
        sort($dInterfaces);

        $this->assertSame(['B'], $cInterfaces);
        $this->assertSame(['A', 'B', 'C'], $dInterfaces);
    }

    public function testReflectedTraitHasNoInterfaces()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector = new ClassReflector($sourceLocator);

        $traitReflection = $reflector->reflect('TraitFixtureTraitA');
        $this->assertSame([], $traitReflection->getInterfaces());
    }

    public function testFetchingFqsenThrowsExceptionWithNonObjectName()
    {
        $sourceLocator = new StringSourceLocator('<?php class Foo {}');
        $reflector = new ClassReflector($sourceLocator);
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        $locatedSource = $sourceLocator->__invoke($identifier);
        $node = new Class_('Foo');
        $reflection = ReflectionClass::createFromNode($reflector, $node, $locatedSource);

        $reflectionClassReflection = new \ReflectionClass(ReflectionClass::class);
        $reflectionClassMethodReflection = $reflectionClassReflection->getMethod('getFqsenFromNamedNode');
        $reflectionClassMethodReflection->setAccessible(true);

        $nameNode = new Name(['int']);

        $this->setExpectedException(\Exception::class, 'Unable to determine FQSEN for named node');
        $reflectionClassMethodReflection->invoke($reflection, $nameNode);
    }

    public function testClassToString()
    {
        $reflection = ReflectionClass::createFromName('BetterReflectionTest\Fixture\ExampleClass');
        $this->assertStringMatchesFormat(
            file_get_contents(__DIR__ . '/../Fixture/ExampleClassExport.txt'),
            $reflection->__toString()
        );
    }

    public function testImplementsReflector()
    {
        $php = '<?php class Foo {}';

        $reflector = new ClassReflector(new StringSourceLocator($php));
        $classInfo = $reflector->reflect('Foo');

        $this->assertInstanceOf(\Reflector::class, $classInfo);
    }

    public function testExportMatchesFormat()
    {
        $this->assertStringMatchesFormat(
            file_get_contents(__DIR__ . '/../Fixture/ExampleClassExport.txt'),
            ReflectionClass::export('BetterReflectionTest\Fixture\ExampleClass')
        );
    }

    public function testExportWithNoClassName()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        ReflectionClass::export();
    }

    public function testToStringWhenImplementingInterface()
    {
        $php = '<?php
            namespace Qux;
            interface Foo {}
            class Bar implements Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Qux\Bar');

        $this->assertStringStartsWith('Class [ <user> class Qux\Bar implements Qux\Foo ] {', $reflection->__toString());
    }

    public function testToStringWhenExtending()
    {
        $php = '<?php
            namespace Qux;
            class Foo {}
            class Bar extends Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Qux\Bar');

        $this->assertStringStartsWith('Class [ <user> class Qux\Bar extends Qux\Foo ] {', $reflection->__toString());
    }

    public function testToStringWhenExtendingAndImplementing()
    {
        $php = '<?php
            namespace Qux;
            interface Foo {}
            interface Bar {}
            class Bat {}
            class Baz extends Bat implements Foo, Bar {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Qux\Baz');

        $this->assertStringStartsWith('Class [ <user> class Qux\Baz extends Qux\Bat implements Qux\Foo, Qux\Bar ] {', $reflection->__toString());
    }
}
