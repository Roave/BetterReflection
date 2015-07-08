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
        $reflection = new ReflectionFunction('BetterReflectionTest\Fixture\myFunction');
        $this->assertSame('myFunction', $reflection->getShortName());
    }
}
