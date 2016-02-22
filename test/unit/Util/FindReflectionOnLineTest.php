<?php

namespace BetterReflectionTest\Util;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Util\FindReflectionOnLine;

/**
 * @covers \BetterReflection\Util\FindReflectionOnLine
 */
class FindReflectionOnLineTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeFindsClass()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 10);

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame('SomeFooClass', $reflection->getName());
    }

    public function testInvokeFindsTrait()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 19);

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame('SomeFooTrait', $reflection->getName());
    }

    public function testInvokeFindsInterface()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 24);

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame('SomeFooInterface', $reflection->getName());
    }

    public function testInvokeFindsMethod()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 14);

        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
        $this->assertSame('someMethod', $reflection->getName());
    }

    public function testInvokeFindsFunction()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 5);

        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
        $this->assertSame('fooFunc', $reflection->getName());
    }

    public function testInvokeReturnsNullWhenNothingFound()
    {
        $finder = new FindReflectionOnLine();
        $this->assertNull($finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 1));
    }
}
