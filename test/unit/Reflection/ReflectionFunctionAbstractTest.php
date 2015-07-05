<?php

namespace BetterReflectionTest\Reflection;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflectionTest\SourceLocator\StringSourceLocatorTest;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflection\ReflectionFunctionAbstract
 */
class ReflectionFunctionAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testIsClosure()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isClosure());
    }

    public function testIsDeprecated()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isDeprecated());
    }

    public function testIsInternal()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isInternal());
    }

    public function variadicProvider()
    {
        return [
            ['<?php function foo($notVariadic) {}', false],
            ['<?php function foo(...$isVariadic) {}', true],
            ['<?php function foo($notVariadic, ...$isVariadic) {}', true],
        ];
    }

    /**
     * @param string $php
     * @param bool $expectingVariadic
     * @dataProvider variadicProvider
     */
    public function testIsVariadic($php, $expectingVariadic)
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertSame($expectingVariadic, $function->isVariadic());
    }
}
