<?php

namespace AsgrimTest;

use Asgrim\Reflector;

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
        $paramInfo = $methodInfo->getParameters()[0];
        $actualValue = $paramInfo->getDefaultValue();

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testGetTypes()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\MethodsTest');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $this->assertSame(['string'], $param1->getTypes());

        $param2 = $method->getParameter('parameter2');
        $this->assertSame(['int', 'float'], $param2->getTypes());
    }
}
