<?php

namespace BetterReflectionTest;

use BetterReflection\Reflector;
use phpDocumentor\Reflection\Types;

class ReflectionParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function setUp()
    {
        global $loader;
        $this->reflector = new Reflector($loader);
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
     * @dataProvider defaultParameterProvider
     */
    public function testDefaultParametersTypes($defaultExpression, $expectedValue)
    {
        $content = "<?php class Foo { public function myMethod(\$var = $defaultExpression) {} }";

        $classInfo = $this->reflector->reflectClassFromString('Foo', $content);
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');
        $actualValue = $paramInfo->getDefaultValue();

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testGetTypeStrings()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodsTest');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $this->assertSame(['string'], $param1->getTypeStrings());

        $param2 = $method->getParameter('parameter2');
        $this->assertSame(['int', 'float'], $param2->getTypeStrings());
    }

    public function testStringCast()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodsTest');
        $method = $classInfo->getMethod('methodWithOptionalParameters');

        $requiredParam = $method->getParameter('parameter');
        $this->assertSame('Parameter #0 [ <required> $parameter ]', (string)$requiredParam);

        $optionalParam = $method->getParameter('optionalParameter');
        $this->assertSame('Parameter #1 [ <optional> $optionalParameter = null ]', (string)$optionalParam);
    }

    public function testGetPositions()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodsTest');

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
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodsTest');

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
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodsTest');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonCallableParam = $method->getParameter('stdClassParameter');
        $this->assertFalse($nonCallableParam->isCallable());

        $callableParam = $method->getParameter('callableParameter');
        $this->assertTrue($callableParam->isCallable());
    }

    public function testIsArray()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\MethodsTest');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonArrayParam = $method->getParameter('stdClassParameter');
        $this->assertFalse($nonArrayParam->isArray());

        $arrayParam = $method->getParameter('arrayParameter');
        $this->assertTrue($arrayParam->isArray());
    }
}
