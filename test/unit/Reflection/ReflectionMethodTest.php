<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflection\ReflectionParameter;
use BetterReflection\SourceLocator\ComposerSourceLocator;

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

    public function modifierProvider()
    {
        return [
            ['publicMethod', 256, ['public']],
            ['privateMethod', 1024, ['private']],
            ['protectedMethod', 512, ['protected']],
            ['finalPublicMethod', 260, ['final', 'public']],
            ['abstractPublicMethod', 258, ['abstract', 'public']],
            ['staticPublicMethod', 257, ['public', 'static']],
            ['noVisibility', 256, ['public']],
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
}
