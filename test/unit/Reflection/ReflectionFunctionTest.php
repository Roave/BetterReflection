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

        self::assertInstanceOf(\Reflector::class, $functionInfo);
    }

    public function testNameMethodsWithNoNamespace()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        self::assertFalse($function->inNamespace());
        self::assertSame('foo', $function->getName());
        self::assertSame('', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInNamespace()
    {
        $php = '<?php namespace A\B { function foo() {} }';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('A\B\foo');

        self::assertTrue($function->inNamespace());
        self::assertSame('A\B\foo', $function->getName());
        self::assertSame('A\B', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInExplicitGlobalNamespace()
    {
        $php = '<?php namespace { function foo() {} }';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        self::assertFalse($function->inNamespace());
        self::assertSame('foo', $function->getName());
        self::assertSame('', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testIsDisabled()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        self::assertFalse($function->isDisabled());
    }

    public function testIsUserDefined()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        self::assertTrue($function->isUserDefined());
    }

    public function testStaticCreationFromName()
    {
        require_once(__DIR__ . '/../Fixture/Functions.php');
        $reflection = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunction');
        self::assertSame('myFunction', $reflection->getShortName());
    }

    public function testCreateFromClosure()
    {
        $myClosure = function () {
            return 5;
        };
        $reflection = ReflectionFunction::createFromClosure($myClosure);
        self::assertSame('{closure}', $reflection->getShortName());
    }

    public function testCreateFromClosureCanReflectTypeHints()
    {
        $myClosure = function (\stdClass $theParam) {
            return 5;
        };
        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        self::assertSame('stdClass', $theParam->getName());
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

        self::assertStringMatchesFormat($expectedStringValue, (string)$functionInfo);
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

        self::assertInternalType('array', $types);
        self::assertCount(1, $types);
        self::assertInstanceOf(Boolean::class, $types[0]);
    }
}
