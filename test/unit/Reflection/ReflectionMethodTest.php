<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use phpDocumentor\Reflection\Types\Integer;
use PhpParser\Node\Stmt\Function_;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\Methods;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionMethod
 */
class ReflectionMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    public function setUp()
    {
        global $loader;
        $this->reflector = new ClassReflector(new ComposerSourceLocator($loader));
    }

    public function testCreateFromName()
    {
        $method = ReflectionMethod::createFromName(\SplDoublyLinkedList::class, 'add');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertSame('add', $method->getName());
    }

    public function testCreateFromInstance()
    {
        $method = ReflectionMethod::createFromInstance(new \SplDoublyLinkedList(), 'add');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertSame('add', $method->getName());
    }

    public function testImplementsReflector()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $methodInfo = $classInfo->getMethod('publicMethod');

        self::assertInstanceOf(\Reflector::class, $methodInfo);
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
     * @param string $methodName
     * @param bool $shouldBePublic
     * @param bool $shouldBePrivate
     * @param bool $shouldBeProtected
     * @param bool $shouldBeFinal
     * @param bool $shouldBeAbstract
     * @param bool $shouldBeStatic
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
    ) {
        $classInfo = $this->reflector->reflect(Methods::class);
        $reflectionMethod = $classInfo->getMethod($methodName);

        self::assertSame($shouldBePublic, $reflectionMethod->isPublic());
        self::assertSame($shouldBePrivate, $reflectionMethod->isPrivate());
        self::assertSame($shouldBeProtected, $reflectionMethod->isProtected());
        self::assertSame($shouldBeFinal, $reflectionMethod->isFinal());
        self::assertSame($shouldBeAbstract, $reflectionMethod->isAbstract());
        self::assertSame($shouldBeStatic, $reflectionMethod->isStatic());
    }

    public function testIsConstructorDestructor()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('__construct');
        self::assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__destruct');
        self::assertTrue($method->isDestructor());
    }

    public function testGetParameters()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');
        $params = $method->getParameters();

        self::assertCount(2, $params);
        self::assertContainsOnlyInstancesOf(ReflectionParameter::class, $params);

        self::assertSame('parameter1', $params[0]->getName());
        self::assertSame('parameter2', $params[1]->getName());
    }

    public function testGetNumberOfParameters()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method1 = $classInfo->getMethod('methodWithParameters');
        self::assertSame(2, $method1->getNumberOfParameters(), 'Failed asserting methodWithParameters has 2 params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        self::assertSame(2, $method2->getNumberOfParameters(), 'Failed asserting methodWithOptionalParameters has 2 params');
    }

    public function testGetNumberOfOptionalParameters()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method1 = $classInfo->getMethod('methodWithParameters');
        self::assertSame(2, $method1->getNumberOfRequiredParameters(), 'Failed asserting methodWithParameters has 2 required params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        self::assertSame(1, $method2->getNumberOfRequiredParameters(), 'Failed asserting methodWithOptionalParameters has 1 required param');
    }

    public function testGetFileName()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod('methodWithParameters');

        $detectedFilename = $method->getFileName();

        self::assertSame('Methods.php', basename($detectedFilename));
    }

    public function testMethodNameWithNamespace()
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);
        $methodInfo = $classInfo->getMethod('someMethod');

        self::assertFalse($methodInfo->inNamespace());
        self::assertSame('someMethod', $methodInfo->getName());
        self::assertSame('', $methodInfo->getNamespaceName());
        self::assertSame('someMethod', $methodInfo->getShortName());
    }

    public function testGetDocBlockReturnTypes()
    {
        $php = '<?php
        class Foo {
            /**
             * @return int
             */
            public function someMethod() {}
        }
        ';

        $methodInfo = (new ClassReflector(new StringSourceLocator($php)))
            ->reflect('Foo')
            ->getMethod('someMethod');

        $types = $methodInfo->getDocBlockReturnTypes();

        self::assertInternalType('array', $types);
        self::assertCount(1, $types);
        self::assertInstanceOf(Integer::class, $types[0]);
    }

    public function modifierProvider() : array
    {
        return [
            ['publicMethod', \ReflectionMethod::IS_PUBLIC, ['public']],
            ['privateMethod', \ReflectionMethod::IS_PRIVATE, ['private']],
            ['protectedMethod', \ReflectionMethod::IS_PROTECTED, ['protected']],
            ['finalPublicMethod', \ReflectionMethod::IS_FINAL | \ReflectionMethod::IS_PUBLIC, ['final', 'public']],
            ['abstractPublicMethod', \ReflectionMethod::IS_ABSTRACT | \ReflectionMethod::IS_PUBLIC, ['abstract', 'public']],
            ['staticPublicMethod', \ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC, ['public', 'static']],
            ['noVisibility', \ReflectionMethod::IS_PUBLIC, ['public']],
        ];
    }

    /**
     * @param string $methodName
     * @param int $expectedModifier
     * @param string[] $expectedModifierNames
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $methodName, int $expectedModifier, array $expectedModifierNames)
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod($methodName);

        self::assertSame($expectedModifier, $method->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            \Reflection::getModifierNames($method->getModifiers())
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
        ];
    }

    /**
     * @param string $class
     * @param string $method
     * @param string|null $expectedPrototype
     * @dataProvider prototypeProvider
     */
    public function testGetPrototype(string $class, string $method, $expectedPrototype)
    {
        $fixture = __DIR__ . '/../Fixture/PrototypeTree.php';
        $reflector = new ClassReflector(new SingleFileSourceLocator($fixture));

        if (null === $expectedPrototype) {
            $this->expectException(MethodPrototypeNotFound::class);
        }

        $b = $reflector->reflect($class)->getMethod($method)->getPrototype();
        self::assertInstanceOf(ReflectionMethod::class, $b);
        self::assertSame($expectedPrototype, $b->getDeclaringClass()->getName());
    }

    public function testGetMethodNodeFailsWhenNodeIsNotClassMethod()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod('publicMethod');

        $methodReflection = new \ReflectionClass(ReflectionFunctionAbstract::class);
        $methodNodeProp = $methodReflection->getProperty('node');
        $methodNodeProp->setAccessible(true);
        $methodNodeProp->setValue($method, new Function_('foo'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a ClassMethod node');
        $method->isPublic();
    }

    public function methodStringRepresentations() : array
    {
        $methods = [
            ['__construct', "Method [ <user, ctor> public method __construct ] {\n  @@ %s/test/unit/Fixture/Methods.php 11 - 13\n}"],
            ['publicMethod', "Method [ <user> public method publicMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 15 - 17\n}"],
            ['privateMethod', "Method [ <user> private method privateMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 19 - 21\n}"],
            ['protectedMethod', "Method [ <user> protected method protectedMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 23 - 25\n}"],
            ['finalPublicMethod', "Method [ <user> final public method finalPublicMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 27 - 29\n}"],
            ['abstractPublicMethod', "Method [ <user> abstract public method abstractPublicMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 31 - 31\n}"],
            ['staticPublicMethod', "Method [ <user> static public method staticPublicMethod ] {\n  @@ %s/test/unit/Fixture/Methods.php 33 - 35\n}"],
            ['noVisibility', "Method [ <user> public method noVisibility ] {\n  @@ %s/test/unit/Fixture/Methods.php 37 - 39\n}"],
            ['__destruct', "Method [ <user, dtor> public method __destruct ] {\n  @@ %s/test/unit/Fixture/Methods.php 41 - 43\n}"],
            ['methodWithParameters', "Method [ <user> public method methodWithParameters ] {\n  @@ %s/test/unit/Fixture/Methods.php 49 - 51\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$parameter1 ]\n    Parameter #1 [ <required> \$parameter2 ]\n  }\n}"],
            ['methodWithOptionalParameters', "Method [ <user> public method methodWithOptionalParameters ] {\n  @@ %s/test/unit/Fixture/Methods.php 53 - 55\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$parameter ]\n    Parameter #1 [ <optional> \$optionalParameter = NULL ]\n  }\n}"],
            ['methodWithExplicitTypedParameters', "Method [ <user> public method methodWithExplicitTypedParameters ] {\n  @@ %s/test/unit/Fixture/Methods.php 57 - 64\n\n  - Parameters [5] {\n    Parameter #0 [ <required> stdClass \$stdClassParameter ]\n    Parameter #1 [ <required> Roave\BetterReflectionTest\Fixture\ClassForHinting \$namespaceClassParameter ]\n    Parameter #2 [ <required> Roave\BetterReflectionTest\Fixture\ClassForHinting \$fullyQualifiedClassParameter ]\n    Parameter #3 [ <required> array \$arrayParameter ]\n    Parameter #4 [ <required> callable \$callableParameter ]\n  }\n}"],
            ['methodWithVariadic', "Method [ <user> public method methodWithVariadic ] {\n  @@ %s/test/unit/Fixture/Methods.php 66 - 68\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$nonVariadicParameter ]\n    Parameter #1 [ <optional> ...\$variadicParameter ]\n  }\n}"],
            ['methodWithReference', "Method [ <user> public method methodWithReference ] {\n  @@ %s/test/unit/Fixture/Methods.php 70 - 72\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$nonRefParameter ]\n    Parameter #1 [ <required> &\$refParameter ]\n  }\n}"],
            ['methodWithNonOptionalDefaultValue', "Method [ <user> public method methodWithNonOptionalDefaultValue ] {\n  @@ %s/test/unit/Fixture/Methods.php 74 - 76\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$firstParameter ]\n    Parameter #1 [ <required> \$secondParameter ]\n  }\n}"],
            ['methodToCheckAllowsNull', "Method [ <user> public method methodToCheckAllowsNull ] {\n  @@ %s/test/unit/Fixture/Methods.php 78 - 80\n\n  - Parameters [3] {\n    Parameter #0 [ <required> \$allowsNull ]\n    Parameter #1 [ <required> stdClass \$hintDisallowNull ]\n    Parameter #2 [ <optional> stdClass or NULL \$hintAllowNull = NULL ]\n  }\n}"],
        ];

        return array_combine(
            array_map(
                function (array $methodData) {
                    return $methodData[0];
                },
                $methods
            ),
            $methods
        );
    }

    /**
     * @param string $methodName
     * @param string $expectedStringValue
     * @dataProvider methodStringRepresentations
     */
    public function testStringCast(string $methodName, string $expectedStringValue)
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod($methodName);

        self::assertStringMatchesFormat($expectedStringValue, (string)$method);
    }
}
