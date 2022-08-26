<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use ClassWithMethodsAndTraitMethods;
use Closure;
use ExtendedClassWithMethodsAndTraitMethods;
use Php4StyleCaseInsensitiveConstruct;
use Php4StyleConstruct;
use PHPUnit\Framework\TestCase;
use Reflection;
use ReflectionClass;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassUsesTraitWithStaticMethod;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\ClassWithNonStaticMethod;
use Roave\BetterReflectionTest\Fixture\ClassWithStaticMethod;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\InterfaceWithMethod;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\Fixture\Php4StyleConstructInNamespace;
use Roave\BetterReflectionTest\Fixture\TraitWithStaticMethod;
use Roave\BetterReflectionTest\Fixture\TraitWithStaticMethodToUse;
use Roave\BetterReflectionTest\Fixture\UpperCaseConstructDestruct;
use SplDoublyLinkedList;
use stdClass;
use TraitFixtureC;
use TraitWithMethod;

use function basename;

/** @covers \Roave\BetterReflection\Reflection\ReflectionMethod */
class ReflectionMethodTest extends TestCase
{
    private Reflector $reflector;

    private Locator $astLocator;

    private SourceStubber $sourceStubber;

    public function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
        $this->reflector     = new DefaultReflector(new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator));
    }

    public function testCreateFromName(): void
    {
        $method = ReflectionMethod::createFromName(SplDoublyLinkedList::class, 'add');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertSame('add', $method->getName());
    }

    public function testCreateFromInstance(): void
    {
        $method = ReflectionMethod::createFromInstance(new SplDoublyLinkedList(), 'add');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertSame('add', $method->getName());
    }

    public function testIsClosure(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod('__construct');

        self::assertFalse($method->isClosure());
    }

    /** @return array<string, array{0: string, 1: bool, 2: bool, 3: bool, 4: bool, 5: bool, 6: bool}> */
    public function visibilityProvider(): array
    {
        return [
            'publicMethod' => ['publicMethod', true, false, false, false, false, false],
            'privateMethod' => ['privateMethod', false, true, false, false, false, false],
            'protectedMethod' => ['protectedMethod', false, false, true, false, false, false],
            'finalPublicMethod' => ['finalPublicMethod', true, false, false, true, false, false],
            'abstractPublicMethod' => ['abstractPublicMethod', true, false, false, false, true, false],
            'staticPublicMethod' => ['staticPublicMethod', true, false, false, false, false, true],
            'noVisibility' => ['publicMethod', true, false, false, false, false, false],
        ];
    }

    /** @dataProvider visibilityProvider */
    public function testVisibilityOfMethods(
        string $methodName,
        bool $shouldBePublic,
        bool $shouldBePrivate,
        bool $shouldBeProtected,
        bool $shouldBeFinal,
        bool $shouldBeAbstract,
        bool $shouldBeStatic,
    ): void {
        $classInfo        = $this->reflector->reflectClass(Methods::class);
        $reflectionMethod = $classInfo->getMethod($methodName);

        self::assertSame($shouldBePublic, $reflectionMethod->isPublic());
        self::assertSame($shouldBePrivate, $reflectionMethod->isPrivate());
        self::assertSame($shouldBeProtected, $reflectionMethod->isProtected());
        self::assertSame($shouldBeFinal, $reflectionMethod->isFinal());
        self::assertSame($shouldBeAbstract, $reflectionMethod->isAbstract());
        self::assertSame($shouldBeStatic, $reflectionMethod->isStatic());
    }

    public function testIsAbstractForMethodInInterface(): void
    {
        $classInfo  = $this->reflector->reflectClass(InterfaceWithMethod::class);
        $methodInfo = $classInfo->getMethod('someMethod');

        self::assertTrue($methodInfo->isAbstract());
    }

    public function testIsConstructorDestructor(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('__construct');
        self::assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__destruct');
        self::assertTrue($method->isDestructor());
    }

    public function testIsConstructorDestructorIsCaseInsensitive(): void
    {
        $classInfo = $this->reflector->reflectClass(UpperCaseConstructDestruct::class);

        $method = $classInfo->getMethod('__CONSTRUCT');
        self::assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__DESTRUCT');
        self::assertTrue($method->isDestructor());
    }

    public function testIsConstructorWhenPhp4Style(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleConstruct.php', $this->astLocator));
        $classInfo = $reflector->reflectClass(Php4StyleConstruct::class);

        $method = $classInfo->getMethod('Php4StyleConstruct');
        self::assertTrue($method->isConstructor());
    }

    public function testsIsConstructorWhenPhp4StyleInNamespace(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleConstructInNamespace.php', $this->astLocator));
        $classInfo = $reflector->reflectClass(Php4StyleConstructInNamespace::class);

        $method = $classInfo->getMethod('Php4StyleConstructInNamespace');
        self::assertFalse($method->isConstructor());
    }

    public function testIsConstructorWhenPhp4StyleCaseInsensitive(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleCaseInsensitiveConstruct.php', $this->astLocator));
        $classInfo = $reflector->reflectClass(Php4StyleCaseInsensitiveConstruct::class);

        $method = $classInfo->getMethod('PHP4STYLECASEINSENSITIVECONSTRUCT');
        self::assertTrue($method->isConstructor());
    }

    public function testGetParameters(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');
        $params = $method->getParameters();

        self::assertCount(2, $params);
        self::assertContainsOnlyInstancesOf(ReflectionParameter::class, $params);

        self::assertSame('parameter1', $params[0]->getName());
        self::assertSame('parameter2', $params[1]->getName());
    }

    public function testGetNumberOfParameters(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method1 = $classInfo->getMethod('methodWithParameters');
        self::assertSame(2, $method1->getNumberOfParameters(), 'Failed asserting methodWithParameters has 2 params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        self::assertSame(2, $method2->getNumberOfParameters(), 'Failed asserting methodWithOptionalParameters has 2 params');
    }

    public function testGetNumberOfOptionalParameters(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method1 = $classInfo->getMethod('methodWithParameters');
        self::assertSame(2, $method1->getNumberOfRequiredParameters(), 'Failed asserting methodWithParameters has 2 required params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        self::assertSame(1, $method2->getNumberOfRequiredParameters(), 'Failed asserting methodWithOptionalParameters has 1 required param');
    }

    public function testGetFileName(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod('methodWithParameters');

        $detectedFilename = $method->getFileName();

        self::assertSame('Methods.php', basename($detectedFilename));
    }

    public function testMethodNameWithNamespace(): void
    {
        $classInfo  = $this->reflector->reflectClass(ExampleClass::class);
        $methodInfo = $classInfo->getMethod('someMethod');

        self::assertFalse($methodInfo->inNamespace());
        self::assertSame('someMethod', $methodInfo->getName());
        self::assertSame('', $methodInfo->getNamespaceName());
        self::assertSame('someMethod', $methodInfo->getShortName());
    }

    public function testMethodNameWithTraitAlias(): void
    {
        $reflector  = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php', $this->astLocator));
        $classInfo  = $reflector->reflectClass(TraitFixtureC::class);
        $methodInfo = $classInfo->getMethod('b_renamed');

        self::assertFalse($methodInfo->inNamespace());
        self::assertSame('b_renamed', $methodInfo->getName());
        self::assertSame('', $methodInfo->getNamespaceName());
        self::assertSame('b_renamed', $methodInfo->getShortName());
    }

    public function testGetObjectReturnTypes(): void
    {
        $php = '<?php
        namespace A;
        class Foo {
            public function someMethod() : object {}
        }
        ';

        $returnType = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))
            ->reflectClass('A\\Foo')
            ->getMethod('someMethod')
            ->getReturnType();

        self::assertInstanceOf(ReflectionType::class, $returnType);
        self::assertTrue($returnType->isBuiltin());
        self::assertSame('object', (string) $returnType);
    }

    /** @return list<array{0: string, 1: int, 2: list<string>}> */
    public function modifierProvider(): array
    {
        return [
            ['publicMethod', CoreReflectionMethod::IS_PUBLIC, ['public']],
            ['privateMethod', CoreReflectionMethod::IS_PRIVATE, ['private']],
            ['protectedMethod', CoreReflectionMethod::IS_PROTECTED, ['protected']],
            ['finalPublicMethod', CoreReflectionMethod::IS_FINAL | CoreReflectionMethod::IS_PUBLIC, ['final', 'public']],
            ['abstractPublicMethod', CoreReflectionMethod::IS_ABSTRACT | CoreReflectionMethod::IS_PUBLIC, ['abstract', 'public']],
            ['staticPublicMethod', CoreReflectionMethod::IS_STATIC | CoreReflectionMethod::IS_PUBLIC, ['public', 'static']],
            ['noVisibility', CoreReflectionMethod::IS_PUBLIC, ['public']],
        ];
    }

    /**
     * @param list<string> $expectedModifierNames
     *
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $methodName, int $expectedModifier, array $expectedModifierNames): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod($methodName);

        self::assertSame($expectedModifier, $method->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            Reflection::getModifierNames($method->getModifiers()),
        );
    }

    /** @return list<array{0: string, 1: string, 2: string|null}> */
    public function prototypeProvider(): array
    {
        return [
            ['Zoom\B', 'foo', 'Zoom\FooInterface'],
            ['Xoom\B', 'foo', 'Xoom\A'],
            ['ClassB', 'foo', 'ClassA'],
            ['ClassC', 'foo', 'FooInterface'],
            ['ClassT', 'bar', null],
            ['Foom\A', 'foo', 'Foom\Foo'],
            ['ClassE', 'boo', 'ClassC'],
            ['ClassF', 'zoo', 'ClassD'],
            ['Construct\Bar', '__construct', null],
            ['Construct\Ipsum', '__construct', 'Construct\Lorem'],
            ['Traits\Foo', 'doFoo', 'Traits\FooInterface'],
        ];
    }

    /** @dataProvider prototypeProvider */
    public function testGetPrototype(string $class, string $method, string|null $expectedPrototype): void
    {
        $fixture   = __DIR__ . '/../Fixture/PrototypeTree.php';
        $reflector = new DefaultReflector(new SingleFileSourceLocator($fixture, $this->astLocator));

        if ($expectedPrototype === null) {
            $this->expectException(MethodPrototypeNotFound::class);
        }

        $b = $reflector->reflectClass($class)->getMethod($method)->getPrototype();
        self::assertInstanceOf(ReflectionMethod::class, $b);
        self::assertSame($expectedPrototype, $b->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: string, 2: string|null}> */
    public function overwrittenMethodProvider(): array
    {
        return [
            ['FooInterface', 'foo', null],
            ['ClassB', 'foo', 'ClassA'],
            ['ClassC', 'foo', null],
            ['ClassD', 'boo', 'ClassC'],
            ['ClassD', 'zoo', 'ClassC'],
        ];
    }

    public function testToString(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        self::assertStringMatchesFormat("Method [ <user> public method publicMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 15 - 17\n}", (string) $classInfo->getMethod('publicMethod'));
    }

    public function testGetDeclaringAndImplementingAndCurrentClassWithMethodFromTrait(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithMethodsAndTraitMethods.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ExtendedClassWithMethodsAndTraitMethods::class);
        $methodReflection = $classReflection->getMethod('methodFromTrait');

        self::assertSame(TraitWithMethod::class, $methodReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getImplementingClass()->getName());
        self::assertSame(ExtendedClassWithMethodsAndTraitMethods::class, $methodReflection->getCurrentClass()->getName());
    }

    public function testGetDeclaringAndImplementingAndCurrentClassWithMethodFromClass(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithMethodsAndTraitMethods.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ClassWithMethodsAndTraitMethods::class);
        $methodReflection = $classReflection->getMethod('methodFromClass');

        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getImplementingClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getCurrentClass()->getName());
        self::assertSame($methodReflection->getDeclaringClass(), $methodReflection->getImplementingClass());
        self::assertSame($methodReflection->getImplementingClass(), $methodReflection->getCurrentClass());
    }

    public function testGetDeclaringAndImplementingAndCurrentClassWithPrivateMethodFromParentClass(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithMethodsAndTraitMethods.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ExtendedClassWithMethodsAndTraitMethods::class)->getParentClass();
        $methodReflection = $classReflection->getMethod('methodFromClass');

        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getImplementingClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getCurrentClass()->getName());
        self::assertSame($methodReflection->getDeclaringClass(), $methodReflection->getImplementingClass());
        self::assertSame($methodReflection->getImplementingClass(), $methodReflection->getCurrentClass());
    }

    public function testGetExtensionName(): void
    {
        $classInfo = (new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber)))->reflectClass(ReflectionClass::class);
        $method    = $classInfo->getMethod('isInternal');

        self::assertSame('Reflection', $method->getExtensionName());
    }

    public function testIsInternal(): void
    {
        $classInfo = (new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber)))->reflectClass(ReflectionClass::class);
        $method    = $classInfo->getMethod('isInternal');

        self::assertTrue($method->isInternal());
    }

    public function testGetClosureOfStaticMethodThrowsExceptionWhenClassDoesNotExist(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static function boo()
    {
    }
}
PHP;

        $classReflection  = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('boo');

        $this->expectException(ClassDoesNotExist::class);

        $methodReflection->getClosure();
    }

    public function testGetClosureOfStaticMethod(): void
    {
        $classWithStaticMethodFile = __DIR__ . '/../Fixture/ClassWithStaticMethod.php';
        require_once $classWithStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($classWithStaticMethodFile, $this->astLocator)))->reflectClass(ClassWithStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $closure = $methodReflection->getClosure();

        self::assertInstanceOf(Closure::class, $closure);
        self::assertSame(3, $closure(1, 2));
    }

    public function testGetClosureOfObjectMethodThrowsExceptionWhenNoObject(): void
    {
        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $this->expectException(NoObjectProvided::class);

        $methodReflection->getClosure(null);
    }

    public function testGetClosureOfObjectMethodThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $this->expectException(ObjectNotInstanceOfClass::class);

        $methodReflection->getClosure(new stdClass());
    }

    public function testGetClosureOfObjectMethod(): void
    {
        $classWithNonStaticMethodFile = __DIR__ . '/../Fixture/ClassWithNonStaticMethod.php';
        require_once $classWithNonStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($classWithNonStaticMethodFile, $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $object = new ClassWithNonStaticMethod();

        $closure = $methodReflection->getClosure($object);

        self::assertInstanceOf(Closure::class, $closure);
        self::assertSame(103, $closure(1, 2));
    }

    public function testInvokeOfStaticMethodThrowsExceptionWhenClassDoesNotExist(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static function boo()
    {
    }
}
PHP;

        $classReflection  = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('boo');

        $this->expectException(ClassDoesNotExist::class);

        $methodReflection->invoke();
    }

    public function testInvokeArgsOfStaticMethodThrowsExceptionWhenClassDoesNotExist(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static function boo()
    {
    }
}
PHP;

        $classReflection  = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('boo');

        $this->expectException(ClassDoesNotExist::class);

        $methodReflection->invokeArgs();
    }

    public function testInvokeOfStaticMethod(): void
    {
        $classWithStaticMethodFile = __DIR__ . '/../Fixture/ClassWithStaticMethod.php';
        require_once $classWithStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($classWithStaticMethodFile, $this->astLocator)))->reflectClass(ClassWithStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        self::assertSame(3, $methodReflection->invoke(null, 1, 2));
        self::assertSame(7, $methodReflection->invokeArgs(null, [3, 4]));
    }

    /**
     * Calling static trait method is deprecated in PHP 8.1, it should only be called on a class using the trait
     *
     * @requires PHP < 8.1
     */
    public function testInvokeOfStaticMethodOnTrait(): void
    {
        $traitWithStaticMethodFile = __DIR__ . '/../Fixture/TraitWithStaticMethod.php';
        require_once $traitWithStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($traitWithStaticMethodFile, $this->astLocator)))->reflectClass(TraitWithStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        self::assertSame(3, $methodReflection->invoke(null, 1, 2));
        self::assertSame(7, $methodReflection->invokeArgs(null, [3, 4]));
    }

    /**
     * Calling static trait method is deprecated in PHP 8.1, it should only be called on a class using the trait
     *
     * @requires PHP < 8.1
     */
    public function testInvokeOfStaticTraitMethodWithStaticClass(): void
    {
        $traitWithUsedStaticMethodFile = __DIR__ . '/../Fixture/ClassUsesTraitWithStaticMethod.php';
        require_once $traitWithUsedStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($traitWithUsedStaticMethodFile, $this->astLocator)))->reflectClass(TraitWithStaticMethodToUse::class);
        $methodReflection = $classReflection->getMethod('getClass');

        self::assertSame(TraitWithStaticMethodToUse::class, $methodReflection->invoke());
    }

    public function testInvokeOfStaticUsedTraitMethodWithStaticClass(): void
    {
        $classWithUsedStaticMethodFile = __DIR__ . '/../Fixture/ClassUsesTraitWithStaticMethod.php';
        require_once $classWithUsedStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($classWithUsedStaticMethodFile, $this->astLocator)))->reflectClass(ClassUsesTraitWithStaticMethod::class);
        $methodReflection = $classReflection->getMethod('getClass');

        self::assertSame(ClassUsesTraitWithStaticMethod::class, $methodReflection->invoke());
    }

    public function testInvokeOfObjectMethodThrowsExceptionWhenNoObject(): void
    {
        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $this->expectException(NoObjectProvided::class);

        $methodReflection->invoke(null);
    }

    public function testInvokeArgsOfObjectMethodThrowsExceptionWhenNoObject(): void
    {
        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $this->expectException(NoObjectProvided::class);

        $methodReflection->invokeArgs(null);
    }

    public function testInvokeOfObjectMethodThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $this->expectException(ObjectNotInstanceOfClass::class);

        $methodReflection->invoke(new stdClass());
    }

    public function testInvokeArgsOfObjectMethodThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $this->expectException(ObjectNotInstanceOfClass::class);

        $methodReflection->invokeArgs(new stdClass());
    }

    public function testInvokeOfObjectMethod(): void
    {
        $classWithNonStaticMethodFile = __DIR__ . '/../Fixture/ClassWithNonStaticMethod.php';
        require_once $classWithNonStaticMethodFile;

        $classReflection  = (new DefaultReflector(new SingleFileSourceLocator($classWithNonStaticMethodFile, $this->astLocator)))->reflectClass(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $object = new ClassWithNonStaticMethod();

        self::assertSame(103, $methodReflection->invoke($object, 1, 2));
        self::assertSame(107, $methodReflection->invoke($object, 3, 4));
    }

    public function testInterfaceMethodBodyAst(): void
    {
        $classInfo  = $this->reflector->reflectClass(InterfaceWithMethod::class);
        $methodInfo = $classInfo->getMethod('someMethod');

        self::assertSame([], $methodInfo->getBodyAst());
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ExampleClass::class);
        $methodReflection = $classReflection->getMethod('__construct');
        $attributes       = $methodReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection = $classReflection->getMethod('methodWithAttributes');
        $attributes       = $methodReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection = $classReflection->getMethod('methodWithAttributes');
        $attributes       = $methodReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection = $classReflection->getMethod('methodWithAttributes');
        $attributes       = $methodReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }

    public function testLocatedSourceForParentMethod(): void
    {
        $parentPhp = <<<'PHP'
            <?php

            class Foo
            {
                public function method(): void
                {
                }
            }
        PHP;

        $childPhp = <<<'PHP'
            <?php

            class Bar extends Foo
            {
            }
        PHP;

        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($parentPhp, $this->astLocator),
            new StringSourceLocator($childPhp, $this->astLocator),
        ]));

        $classReflection  = $reflector->reflectClass('Bar');
        $methodReflection = $classReflection->getMethod('method');

        self::assertStringMatchesFormat(
            '%Aclass Foo%A{%A}%A',
            $methodReflection->getLocatedSource()->getSource(),
        );
    }

    public function testLocatedSourceForTraitMethod(): void
    {
        $parentPhp = <<<'PHP'
            <?php

            trait Foo
            {
                public function method(): void
                {
                }
            }
        PHP;

        $childPhp = <<<'PHP'
            <?php

            class Bar
            {
                use Foo;
            }
        PHP;

        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($parentPhp, $this->astLocator),
            new StringSourceLocator($childPhp, $this->astLocator),
        ]));

        $classReflection  = $reflector->reflectClass('Bar');
        $methodReflection = $classReflection->getMethod('method');

        self::assertStringMatchesFormat(
            '%Atrait Foo%A{%A}%A',
            $methodReflection->getLocatedSource()->getSource(),
        );
    }

    public function testUnionTypeWithNullDefaultValue(): void
    {
        $php       = <<<'PHP'
            <?php

            class Foo
            {
                public function method(string|array $p = null): void
                {
                }
            }
        PHP;
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $class     = $reflector->reflectClass('Foo');
        $method    = $class->getMethod('method');
        $parameter = $method->getParameter('p');
        self::assertTrue($parameter->allowsNull());
        $parameterType = $parameter->getType();
        self::assertInstanceOf(ReflectionUnionType::class, $parameterType);
        self::assertTrue($parameterType->allowsNull());
        self::assertSame('string|array|null', (string) $parameterType);
    }

    public function testNullableUnionTypeWithNullDefaultValue(): void
    {
        $php       = <<<'PHP'
            <?php

            class Foo
            {
                public function method(string|array|null $p = null): void
                {
                }
            }
        PHP;
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $class     = $reflector->reflectClass('Foo');
        $method    = $class->getMethod('method');
        $parameter = $method->getParameter('p');
        self::assertTrue($parameter->allowsNull());
        $parameterType = $parameter->getType();
        self::assertInstanceOf(ReflectionUnionType::class, $parameterType);
        self::assertTrue($parameterType->allowsNull());
        self::assertSame('string|array|null', (string) $parameterType);
    }
}
