<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use BetterReflection\Reflection\ReflectionFunctionAbstract;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflection\ReflectionParameter;
use BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
use phpDocumentor\Reflection\Types\Integer;
use PhpParser\Node\Stmt\Function_;
use BetterReflection\Reflection\ReflectionVariable;

/**
 * @covers \BetterReflection\Reflection\ReflectionMethod
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

        $this->assertInstanceOf(ReflectionMethod::class, $method);
        $this->assertSame('add', $method->getName());
    }

    public function testCreateFromInstance()
    {
        $method = ReflectionMethod::createFromInstance(new \SplDoublyLinkedList(), 'add');

        $this->assertInstanceOf(ReflectionMethod::class, $method);
        $this->assertSame('add', $method->getName());
    }

    public function testImplementsReflector()
    {
        $classInfo = $this->reflector->reflect('BetterReflectionTest\Fixture\Methods');
        $methodInfo = $classInfo->getMethod('publicMethod');

        $this->assertInstanceOf(\Reflector::class, $methodInfo);
    }

    /**
     * @return array
     */
    public function visibilityProvider()
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
     * @param string $method
     * @param bool $shouldBePublic
     * @param bool $shouldBePrivate
     * @param bool $shouldBeProtected
     * @param bool $shouldBeFinal
     * @param bool $shouldBeAbstract
     * @param bool $shouldBeStatic
     * @dataProvider visibilityProvider
     */
    public function testVisibilityOfMethods($method, $shouldBePublic, $shouldBePrivate, $shouldBeProtected, $shouldBeFinal, $shouldBeAbstract, $shouldBeStatic)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod($method);

        $this->assertSame($shouldBePublic, $method->isPublic());
        $this->assertSame($shouldBePrivate, $method->isPrivate());
        $this->assertSame($shouldBeProtected, $method->isProtected());
        $this->assertSame($shouldBeFinal, $method->isFinal());
        $this->assertSame($shouldBeAbstract, $method->isAbstract());
        $this->assertSame($shouldBeStatic, $method->isStatic());
    }

    public function testIsConstructorDestructor()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('__construct');
        $this->assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__destruct');
        $this->assertTrue($method->isDestructor());
    }

    public function testGetParameters()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithParameters');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertContainsOnlyInstancesOf(ReflectionParameter::class, $params);

        $this->assertSame('parameter1', $params[0]->getName());
        $this->assertSame('parameter2', $params[1]->getName());
    }

    public function testGetNumberOfParameters()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method1 = $classInfo->getMethod('methodWithParameters');
        $this->assertSame(2, $method1->getNumberOfParameters(), 'Failed asserting methodWithParameters has 2 params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        $this->assertSame(2, $method2->getNumberOfParameters(), 'Failed asserting methodWithOptionalParameters has 2 params');
    }

    public function testGetNumberOfOptionalParameters()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method1 = $classInfo->getMethod('methodWithParameters');
        $this->assertSame(2, $method1->getNumberOfRequiredParameters(), 'Failed asserting methodWithParameters has 2 required params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        $this->assertSame(1, $method2->getNumberOfRequiredParameters(), 'Failed asserting methodWithOptionalParameters has 1 required param');
    }

    public function testGetFileName()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithParameters');

        $detectedFilename = $method->getFileName();

        $this->assertSame('Methods.php', basename($detectedFilename));
    }

    public function testMethodNameWithNamespace()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $methodInfo = $classInfo->getMethod('someMethod');

        $this->assertFalse($methodInfo->inNamespace());
        $this->assertSame('someMethod', $methodInfo->getName());
        $this->assertSame('', $methodInfo->getNamespaceName());
        $this->assertSame('someMethod', $methodInfo->getShortName());
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

        $this->assertInternalType('array', $types);
        $this->assertCount(1, $types);
        $this->assertInstanceOf(Integer::class, $types[0]);
    }

    public function modifierProvider()
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
    public function testGetModifiers($methodName, $expectedModifier, array $expectedModifierNames)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod($methodName);

        $this->assertSame($expectedModifier, $method->getModifiers());
        $this->assertSame(
            $expectedModifierNames,
            \Reflection::getModifierNames($method->getModifiers())
        );
    }

    public function prototypeProvider()
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
    public function testGetPrototype($class, $method, $expectedPrototype)
    {
        $fixture = __DIR__ . '/../Fixture/PrototypeTree.php';
        $reflector = new ClassReflector(new SingleFileSourceLocator($fixture));

        if (null === $expectedPrototype) {
            $this->expectException(MethodPrototypeNotFound::class);
        }

        $b = $reflector->reflect($class)->getMethod($method)->getPrototype();
        $this->assertInstanceOf(ReflectionMethod::class, $b);
        $this->assertSame($expectedPrototype, $b->getDeclaringClass()->getName());
    }

    public function testGetMethodNodeFailsWhenNodeIsNotClassMethod()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('publicMethod');

        $methodReflection = new \ReflectionClass(ReflectionFunctionAbstract::class);
        $methodNodeProp = $methodReflection->getProperty('node');
        $methodNodeProp->setAccessible(true);
        $methodNodeProp->setValue($method, new Function_('foo'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a ClassMethod node');
        $method->isPublic();
    }

    public function methodStringRepresentations()
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
            ['methodWithExplicitTypedParameters', "Method [ <user> public method methodWithExplicitTypedParameters ] {\n  @@ %s/test/unit/Fixture/Methods.php 57 - 64\n\n  - Parameters [5] {\n    Parameter #0 [ <required> stdClass \$stdClassParameter ]\n    Parameter #1 [ <required> BetterReflectionTest\Fixture\ClassForHinting \$namespaceClassParameter ]\n    Parameter #2 [ <required> BetterReflectionTest\Fixture\ClassForHinting \$fullyQualifiedClassParameter ]\n    Parameter #3 [ <required> array \$arrayParameter ]\n    Parameter #4 [ <required> callable \$callableParameter ]\n  }\n}"],
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
    public function testStringCast($methodName, $expectedStringValue)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod($methodName);

        $this->assertStringMatchesFormat($expectedStringValue, (string)$method);
    }

    public function testGetVariables()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodVariables');
        $variables = $classInfo->getMethod('methodOne')->getVariables();
        $this->assertCount(2, $variables);
        $this->assertContainsOnlyInstancesOf(ReflectionVariable::class, $variables);
    }
}
