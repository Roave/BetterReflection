<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionFunction
 */
class ReflectionFunctionTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsReflector()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $this->assertInstanceOf(\Reflector::class, $functionInfo);
    }

    public function testNameMethodsWithNoNamespace()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->inNamespace());
        $this->assertSame('foo', $function->getName());
        $this->assertSame('', $function->getNamespaceName());
        $this->assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInNamespace()
    {
        $php = '<?php namespace A\B { function foo() {} }';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('A\B\foo');

        $this->assertTrue($function->inNamespace());
        $this->assertSame('A\B\foo', $function->getName());
        $this->assertSame('A\B', $function->getNamespaceName());
        $this->assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInExplicitGlobalNamespace()
    {
        $php = '<?php namespace { function foo() {} }';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->inNamespace());
        $this->assertSame('foo', $function->getName());
        $this->assertSame('', $function->getNamespaceName());
        $this->assertSame('foo', $function->getShortName());
    }

    public function testIsDisabled()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isDisabled());
    }

    public function testIsUserDefined()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertTrue($function->isUserDefined());
    }

    public function testStaticCreationFromName()
    {
        require_once(__DIR__ . '/../Fixture/Functions.php');
        $reflection = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunction');
        $this->assertSame('myFunction', $reflection->getShortName());
    }

    public function testCreateFromClosure()
    {
        $myClosure = function () {
            return 5;
        };
        $reflection = ReflectionFunction::createFromClosure($myClosure);
        $this->assertSame('{closure}', $reflection->getShortName());
    }

    public function testCreateFromClosureCanReflectTypeHints()
    {
        $myClosure = function (\stdClass $theParam) {
            return 5;
        };
        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        $this->assertSame('stdClass', $theParam->getName());
    }

    public function functionStringRepresentations()
    {
        return [
            ['Roave\BetterReflectionTest\Fixture\myFunction', "Function [ <user> function Roave\BetterReflectionTest\Fixture\myFunction ] {\n  @@ %s/test/unit/Fixture/Functions.php 5 - 6\n}"],
            ['Roave\BetterReflectionTest\Fixture\myFunctionWithParams', "Function [ <user> function Roave\BetterReflectionTest\Fixture\myFunctionWithParams ] {\n  @@ %s/test/unit/Fixture/Functions.php 8 - 9\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
        ];
    }

    /**
     * @param string $functionName
     * @param string $expectedStringValue
     * @dataProvider functionStringRepresentations
     */
    public function testStringCast($functionName, $expectedStringValue)
    {
        require_once(__DIR__ . '/../Fixture/Functions.php');
        $functionInfo = ReflectionFunction::createFromName($functionName);

        $this->assertStringMatchesFormat($expectedStringValue, (string)$functionInfo);
    }

    public function testGetDocBlockReturnTypes()
    {
        $php = '<?php
            /**
             * @return bool
             */
            function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $types = $function->getDocBlockReturnTypes();

        $this->assertInternalType('array', $types);
        $this->assertCount(1, $types);
        $this->assertInstanceOf(Boolean::class, $types[0]);
    }
}
