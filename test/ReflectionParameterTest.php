<?php

namespace BetterReflectionTest;

use BetterReflection\Reflector\ClassReflector;
use phpDocumentor\Reflection\Types;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;

class ReflectionParameterTest extends \PHPUnit_Framework_TestCase
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

    public function defaultParameterProvider()
    {
        return [
            ['1', 1],
            ['"hello"', 'hello'],
            ['null', null],
            ['1.1', 1.1],
            ['[]', []],
            ['false', false],
            ['true', true],
        ];
    }

    /**
     * @param string $defaultExpression
     * @param mixed $expectedValue
     * @dataProvider defaultParameterProvider
     */
    public function testDefaultParametersTypes($defaultExpression, $expectedValue)
    {
        $content = "<?php class Foo { public function myMethod(\$var = $defaultExpression) {} }";

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');
        $actualValue = $paramInfo->getDefaultValue();

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testGetDocBlockTypeStrings()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $this->assertSame(['string'], $param1->getDocBlockTypeStrings());

        $param2 = $method->getParameter('parameter2');
        $this->assertSame(['int', 'float'], $param2->getDocBlockTypeStrings());
    }

    public function testStringCast()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithOptionalParameters');

        $requiredParam = $method->getParameter('parameter');
        $this->assertSame('Parameter #0 [ <required> $parameter ]', (string)$requiredParam);

        $optionalParam = $method->getParameter('optionalParameter');
        $this->assertSame('Parameter #1 [ <optional> $optionalParameter = null ]', (string)$optionalParam);
    }

    public function testGetPositions()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $this->assertSame(0, $param1->getPosition());

        $param2 = $method->getParameter('parameter2');
        $this->assertSame(1, $param2->getPosition());
    }

    public function typeHintProvider()
    {
        return [
            ['stdClassParameter', Types\Object_::class, '\stdClass', 'stdClass'],
            ['fullyQualifiedClassParameter', Types\Object_::class, '\BetterReflectionTest\Fixture\ClassForHinting', 'ClassForHinting'],
            ['arrayParameter', Types\Array_::class],
            ['callableParameter', Types\Callable_::class],

            // @todo Currently failing as we cannot resolve this properly yet
            //['namespaceClassParameter', Types\Object__::class, 'ClassForHinting'],
        ];
    }

    /**
     * @dataProvider typeHintProvider
     * @param string $parameterToTest
     * @param string $expectedType
     * @param string|null $expectedFqsen
     * @param string|null $expectedFqsenName
     */
    public function testGetTypeHint($parameterToTest, $expectedType, $expectedFqsen = null, $expectedFqsenName = null)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $type = $method->getParameter($parameterToTest)->getTypeHint();
        $this->assertInstanceOf($expectedType, $type);

        if (null !== $expectedFqsen) {
            $this->assertSame($expectedFqsen, (string)$type->getFqsen());
        }

        if (null !== $expectedFqsenName) {
            $this->assertSame($expectedFqsenName, $type->getFqsen()->getName());
        }
    }

    public function testIsCallable()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonCallableParam = $method->getParameter('stdClassParameter');
        $this->assertFalse($nonCallableParam->isCallable());

        $callableParam = $method->getParameter('callableParameter');
        $this->assertTrue($callableParam->isCallable());
    }

    public function testIsArray()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonArrayParam = $method->getParameter('stdClassParameter');
        $this->assertFalse($nonArrayParam->isArray());

        $arrayParam = $method->getParameter('arrayParameter');
        $this->assertTrue($arrayParam->isArray());
    }

    public function testIsVariadic()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        $this->assertFalse($nonVariadicParam->isVariadic());

        $variadicParam = $method->getParameter('variadicParameter');
        $this->assertTrue($variadicParam->isVariadic());
    }

    public function testIsPassedByReference()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithReference');

        $nonRefParam = $method->getParameter('nonRefParameter');
        $this->assertFalse($nonRefParam->isPassedByReference());
        $this->assertTrue($nonRefParam->canBePassedByValue());

        $refParam = $method->getParameter('refParameter');
        $this->assertTrue($refParam->isPassedByReference());
        $this->assertFalse($refParam->canBePassedByValue());
    }

    public function testGetDefaultValueAndIsOptional()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithNonOptionalDefaultValue');

        $firstParam = $method->getParameter('firstParameter');
        $this->assertFalse($firstParam->isOptional());
        $this->assertTrue($firstParam->isDefaultValueAvailable());

        $secondParam = $method->getParameter('secondParameter');
        $this->assertFalse($secondParam->isOptional());
        $this->assertFalse($secondParam->isDefaultValueAvailable());
    }

    public function testAllowsNull()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodToCheckAllowsNull');

        $firstParam = $method->getParameter('allowsNull');
        $this->assertTrue($firstParam->allowsNull());

        $secondParam = $method->getParameter('hintDisallowNull');
        $this->assertFalse($secondParam->allowsNull());

        $thirdParam = $method->getParameter('hintAllowNull');
        $this->assertTrue($thirdParam->allowsNull());
    }

    public function testIsDefaultValueConstantAndGetDefaultValueConstantName()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithConstAsDefault');

        $intDefault = $method->getParameter('intDefault');
        $this->assertFalse($intDefault->isDefaultValueConstant());

        $constDefault = $method->getParameter('constDefault');
        $this->assertTrue($constDefault->isDefaultValueConstant());
        $this->assertSame('SOME_CONST', $constDefault->getDefaultValueConstantName());

        $definedDefault = $method->getParameter('definedDefault');
        $this->assertTrue($definedDefault->isDefaultValueConstant());
        $this->assertSame('SOME_DEFINED_VALUE', $definedDefault->getDefaultValueConstantName());
    }
}
