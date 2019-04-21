<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use A\Foo;
use ClassWithMethodsAndTraitMethods;
use Closure;
use ExtendedClassWithMethodsAndTraitMethods;
use Php4StyleCaseInsensitiveConstruct;
use Php4StyleConstruct;
use phpDocumentor\Reflection\Types\Integer;
use PHPUnit\Framework\TestCase;
use Reflection;
use ReflectionClass;
use ReflectionMethod as CoreReflectionMethod;
use Reflector;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\ClassWithNonStaticMethod;
use Roave\BetterReflectionTest\Fixture\ClassWithStaticMethod;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\InterfaceWithMethod;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\Fixture\Php4StyleConstructInNamespace;
use Roave\BetterReflectionTest\Fixture\UpperCaseConstructDestruct;
use SplDoublyLinkedList;
use stdClass;
use TraitWithMethod;
use function basename;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionMethod
 */
class ReflectionMethodTest extends TestCase
{
    /** @var ClassReflector */
    private $reflector;

    /** @var Locator */
    private $astLocator;

    /** @var SourceStubber */
    private $sourceStubber;

    public function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
        $this->reflector     = new ClassReflector(new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator));
    }

    public function testCreateFromName() : void
    {
        $method = ReflectionMethod::createFromName(SplDoublyLinkedList::class, 'add');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertSame('add', $method->getName());
    }

    public function testCreateFromInstance() : void
    {
        $method = ReflectionMethod::createFromInstance(new SplDoublyLinkedList(), 'add');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertSame('add', $method->getName());
    }

    public function testImplementsReflector() : void
    {
        $classInfo  = $this->reflector->reflect(Methods::class);
        $methodInfo = $classInfo->getMethod('publicMethod');

        self::assertInstanceOf(Reflector::class, $methodInfo);
    }

    /**
     * @return array
     */
    public function visibilityProvider() : array
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

    /**
     * @dataProvider visibilityProvider
     */
    public function testVisibilityOfMethods(
        string $methodName,
        bool $shouldBePublic,
        bool $shouldBePrivate,
        bool $shouldBeProtected,
        bool $shouldBeFinal,
        bool $shouldBeAbstract,
        bool $shouldBeStatic
    ) : void {
        $classInfo        = $this->reflector->reflect(Methods::class);
        $reflectionMethod = $classInfo->getMethod($methodName);

        self::assertSame($shouldBePublic, $reflectionMethod->isPublic());
        self::assertSame($shouldBePrivate, $reflectionMethod->isPrivate());
        self::assertSame($shouldBeProtected, $reflectionMethod->isProtected());
        self::assertSame($shouldBeFinal, $reflectionMethod->isFinal());
        self::assertSame($shouldBeAbstract, $reflectionMethod->isAbstract());
        self::assertSame($shouldBeStatic, $reflectionMethod->isStatic());
    }

    public function testIsConstructorDestructor() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('__construct');
        self::assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__destruct');
        self::assertTrue($method->isDestructor());
    }

    public function testIsConstructorDestructorIsCaseInsensitive() : void
    {
        $classInfo = $this->reflector->reflect(UpperCaseConstructDestruct::class);

        $method = $classInfo->getMethod('__CONSTRUCT');
        self::assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__DESTRUCT');
        self::assertTrue($method->isDestructor());
    }

    public function testIsConstructorWhenPhp4Style() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleConstruct.php', $this->astLocator));
        $classInfo = $reflector->reflect(Php4StyleConstruct::class);

        $method = $classInfo->getMethod('Php4StyleConstruct');
        self::assertTrue($method->isConstructor());
    }

    public function testsIsConstructorWhenPhp4StyleInNamespace() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleConstructInNamespace.php', $this->astLocator));
        $classInfo = $reflector->reflect(Php4StyleConstructInNamespace::class);

        $method = $classInfo->getMethod('Php4StyleConstructInNamespace');
        self::assertFalse($method->isConstructor());
    }

    public function testIsConstructorWhenPhp4StyleCaseInsensitive() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleCaseInsensitiveConstruct.php', $this->astLocator));
        $classInfo = $reflector->reflect(Php4StyleCaseInsensitiveConstruct::class);

        $method = $classInfo->getMethod('PHP4STYLECASEINSENSITIVECONSTRUCT');
        self::assertTrue($method->isConstructor());
    }

    public function testGetParameters() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');
        $params = $method->getParameters();

        self::assertCount(2, $params);
        self::assertContainsOnlyInstancesOf(ReflectionParameter::class, $params);

        self::assertSame('parameter1', $params[0]->getName());
        self::assertSame('parameter2', $params[1]->getName());
    }

    public function testGetNumberOfParameters() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method1 = $classInfo->getMethod('methodWithParameters');
        self::assertSame(2, $method1->getNumberOfParameters(), 'Failed asserting methodWithParameters has 2 params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        self::assertSame(2, $method2->getNumberOfParameters(), 'Failed asserting methodWithOptionalParameters has 2 params');
    }

    public function testGetNumberOfOptionalParameters() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method1 = $classInfo->getMethod('methodWithParameters');
        self::assertSame(2, $method1->getNumberOfRequiredParameters(), 'Failed asserting methodWithParameters has 2 required params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        self::assertSame(1, $method2->getNumberOfRequiredParameters(), 'Failed asserting methodWithOptionalParameters has 1 required param');
    }

    public function testGetFileName() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method    = $classInfo->getMethod('methodWithParameters');

        $detectedFilename = $method->getFileName();

        self::assertSame('Methods.php', basename($detectedFilename));
    }

    public function testMethodNameWithNamespace() : void
    {
        $classInfo  = $this->reflector->reflect(ExampleClass::class);
        $methodInfo = $classInfo->getMethod('someMethod');

        self::assertFalse($methodInfo->inNamespace());
        self::assertSame('someMethod', $methodInfo->getName());
        self::assertSame('', $methodInfo->getNamespaceName());
        self::assertSame('someMethod', $methodInfo->getShortName());
    }

    public function testGetDocBlockReturnTypes() : void
    {
        $php = '<?php
        class Foo {
            /**
             * @return int
             */
            public function someMethod() {}
        }
        ';

        $methodInfo = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))
            ->reflect('Foo')
            ->getMethod('someMethod');

        $types = $methodInfo->getDocBlockReturnTypes();

        self::assertInternalType('array', $types);
        self::assertCount(1, $types);
        self::assertInstanceOf(Integer::class, $types[0]);
    }

    public function testGetObjectReturnTypes() : void
    {
        $php = '<?php
        namespace A;
        class Foo {
            public function someMethod() : object {}
        }
        ';

        $returnType = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))
            ->reflect(Foo::class)
            ->getMethod('someMethod')
            ->getReturnType();

        self::assertInstanceOf(ReflectionType::class, $returnType);
        self::assertTrue($returnType->isBuiltin());
        self::assertSame('object', (string) $returnType);
    }

    public function modifierProvider() : array
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
     * @param string[] $expectedModifierNames
     *
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $methodName, int $expectedModifier, array $expectedModifierNames) : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method    = $classInfo->getMethod($methodName);

        self::assertSame($expectedModifier, $method->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            Reflection::getModifierNames($method->getModifiers())
        );
    }

    public function prototypeProvider() : array
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
        ];
    }

    /**
     * @dataProvider prototypeProvider
     */
    public function testGetPrototype(string $class, string $method, ?string $expectedPrototype) : void
    {
        $fixture   = __DIR__ . '/../Fixture/PrototypeTree.php';
        $reflector = new ClassReflector(new SingleFileSourceLocator($fixture, $this->astLocator));

        if ($expectedPrototype === null) {
            $this->expectException(MethodPrototypeNotFound::class);
        }

        $b = $reflector->reflect($class)->getMethod($method)->getPrototype();
        self::assertInstanceOf(ReflectionMethod::class, $b);
        self::assertSame($expectedPrototype, $b->getDeclaringClass()->getName());
    }

    public function overwrittenMethodProvider() : array
    {
        return [
            ['FooInterface', 'foo', null],
            ['ClassB', 'foo', 'ClassA'],
            ['ClassC', 'foo', null],
            ['ClassD', 'boo', 'ClassC'],
            ['ClassD', 'zoo', 'ClassC'],
        ];
    }

    public function testToString() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        self::assertStringMatchesFormat("Method [ <user> public method publicMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 15 - 17\n}", (string) $classInfo->getMethod('publicMethod'));
    }

    public function testGetDeclaringAndImplementingClassWithMethodFromTrait() : void
    {
        $classReflector   = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithMethodsAndTraitMethods.php', $this->astLocator));
        $classReflection  = $classReflector->reflect(ClassWithMethodsAndTraitMethods::class);
        $methodReflection = $classReflection->getMethod('methodFromTrait');

        self::assertSame(TraitWithMethod::class, $methodReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getImplementingClass()->getName());
        self::assertNotSame($methodReflection->getDeclaringClass(), $methodReflection->getImplementingClass());
    }

    public function testGetDeclaringAndImplementingClassWithMethodFromClass() : void
    {
        $classReflector   = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithMethodsAndTraitMethods.php', $this->astLocator));
        $classReflection  = $classReflector->reflect(ClassWithMethodsAndTraitMethods::class);
        $methodReflection = $classReflection->getMethod('methodFromClass');

        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getImplementingClass()->getName());
        self::assertSame($methodReflection->getDeclaringClass(), $methodReflection->getImplementingClass());
    }

    public function testGetDeclaringAndImplementingClassWithMethodFromParentClass() : void
    {
        $classReflector   = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithMethodsAndTraitMethods.php', $this->astLocator));
        $classReflection  = $classReflector->reflect(ExtendedClassWithMethodsAndTraitMethods::class)->getParentClass();
        $methodReflection = $classReflection->getMethod('methodFromClass');

        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithMethodsAndTraitMethods::class, $methodReflection->getImplementingClass()->getName());
        self::assertSame($methodReflection->getDeclaringClass(), $methodReflection->getImplementingClass());
    }

    public function testGetExtensionName() : void
    {
        $classInfo = (new ClassReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber)))->reflect(ReflectionClass::class);
        $method    = $classInfo->getMethod('isInternal');

        self::assertSame('Reflection', $method->getExtensionName());
    }

    public function testIsInternal() : void
    {
        $classInfo = (new ClassReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber)))->reflect(ReflectionClass::class);
        $method    = $classInfo->getMethod('isInternal');

        self::assertTrue($method->isInternal());
    }

    public function testGetClosureOfStaticMethodThrowsExceptionWhenClassDoesNotExist() : void
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

        $this->expectException(ClassDoesNotExist::class);

        $classReflection  = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $methodReflection = $classReflection->getMethod('boo');

        $methodReflection->getClosure();
    }

    public function testGetClosureOfStaticMethod() : void
    {
        $classWithStaticMethodFile = __DIR__ . '/../Fixture/ClassWithStaticMethod.php';
        require_once $classWithStaticMethodFile;

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator($classWithStaticMethodFile, $this->astLocator)))->reflect(ClassWithStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $closure = $methodReflection->getClosure();

        self::assertInstanceOf(Closure::class, $closure);
        self::assertSame(3, $closure(1, 2));
    }

    public function testGetClosureOfObjectMethodThrowsExceptionWhenNoObject() : void
    {
        $this->expectException(NoObjectProvided::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->getClosure(null);
    }

    public function testGetClosureOfObjectMethodThrowsExceptionWhenObjectNotAnObject() : void
    {
        $this->expectException(NotAnObject::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->getClosure(123);
    }

    public function testGetClosureOfObjectMethodThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $this->expectException(ObjectNotInstanceOfClass::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->getClosure(new stdClass());
    }

    public function testGetClosureOfObjectMethod() : void
    {
        $classWithNonStaticMethodFile = __DIR__ . '/../Fixture/ClassWithNonStaticMethod.php';
        require_once $classWithNonStaticMethodFile;

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator($classWithNonStaticMethodFile, $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $object = new ClassWithNonStaticMethod();

        $closure = $methodReflection->getClosure($object);

        self::assertInstanceOf(Closure::class, $closure);
        self::assertSame(103, $closure(1, 2));
    }

    public function testInvokeOfStaticMethodThrowsExceptionWhenClassDoesNotExist() : void
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

        $this->expectException(ClassDoesNotExist::class);

        $classReflection  = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $methodReflection = $classReflection->getMethod('boo');

        $methodReflection->invoke();
    }

    public function testInvokeArgsOfStaticMethodThrowsExceptionWhenClassDoesNotExist() : void
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

        $this->expectException(ClassDoesNotExist::class);

        $classReflection  = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $methodReflection = $classReflection->getMethod('boo');

        $methodReflection->invokeArgs();
    }

    public function testInvokeOfStaticMethod() : void
    {
        $classWithStaticMethodFile = __DIR__ . '/../Fixture/ClassWithStaticMethod.php';
        require_once $classWithStaticMethodFile;

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator($classWithStaticMethodFile, $this->astLocator)))->reflect(ClassWithStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        self::assertSame(3, $methodReflection->invoke(null, 1, 2));
        self::assertSame(7, $methodReflection->invokeArgs(null, [3, 4]));
    }

    public function testInvokeOfObjectMethodThrowsExceptionWhenNoObject() : void
    {
        $this->expectException(NoObjectProvided::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->invoke(null);
    }

    public function testInvokeArgsOfObjectMethodThrowsExceptionWhenNoObject() : void
    {
        $this->expectException(NoObjectProvided::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->invokeArgs(null);
    }

    public function testInvokeOfObjectMethodThrowsExceptionWhenObjectNotAnObject() : void
    {
        $this->expectException(NotAnObject::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->invoke(123);
    }

    public function testInvokeArgsOfObjectMethodThrowsExceptionWhenObjectNotAnObject() : void
    {
        $this->expectException(NotAnObject::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->invokeArgs(123);
    }

    public function testInvokeOfObjectMethodThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $this->expectException(ObjectNotInstanceOfClass::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->invoke(new stdClass());
    }

    public function testInvokeArgsOfObjectMethodThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $this->expectException(ObjectNotInstanceOfClass::class);

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithNonStaticMethod.php', $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $methodReflection->invokeArgs(new stdClass());
    }

    public function testInvokeOfObjectMethod() : void
    {
        $classWithNonStaticMethodFile = __DIR__ . '/../Fixture/ClassWithNonStaticMethod.php';
        require_once $classWithNonStaticMethodFile;

        $classReflection  = (new ClassReflector(new SingleFileSourceLocator($classWithNonStaticMethodFile, $this->astLocator)))->reflect(ClassWithNonStaticMethod::class);
        $methodReflection = $classReflection->getMethod('sum');

        $object = new ClassWithNonStaticMethod();

        self::assertSame(103, $methodReflection->invoke($object, 1, 2));
        self::assertSame(107, $methodReflection->invoke($object, 3, 4));
    }

    public function testInterfaceMethodBodyAst() : void
    {
        $classInfo  = $this->reflector->reflect(InterfaceWithMethod::class);
        $methodInfo = $classInfo->getMethod('someMethod');

        self::assertSame([], $methodInfo->getBodyAst());
    }
}
