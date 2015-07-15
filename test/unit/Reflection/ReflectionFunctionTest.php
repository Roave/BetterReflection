<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflection\ReflectionFunction
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

    public function testStaticCreation()
    {
        require_once(__DIR__ . '/../Fixture/Functions.php');
        $reflection = ReflectionFunction::createFromName('BetterReflectionTest\Fixture\myFunction');
        $this->assertSame('myFunction', $reflection->getShortName());
    }

    public function functionStringRepresentations()
    {
        return [
            ['BetterReflectionTest\Fixture\myFunction', "Function [ <user> function BetterReflectionTest\Fixture\myFunction ] {\n  @@ /home/james/workspace/better-reflection/test/unit/Fixture/Functions.php 5 - 6\n}"],
            ['BetterReflectionTest\Fixture\myFunctionWithParams', "Function [ <user> function BetterReflectionTest\Fixture\myFunctionWithParams ] {\n  @@ /home/james/workspace/better-reflection/test/unit/Fixture/Functions.php 8 - 9\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
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

        $this->assertSame($expectedStringValue, (string)$functionInfo);
    }
}
