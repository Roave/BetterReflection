<?php

namespace Roave\BetterReflectionTest\Util;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Util\FindReflectionOnLine;

/**
 * @covers \Roave\BetterReflection\Util\FindReflectionOnLine
 */
class FindReflectionOnLineTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeFindsClass()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 10);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooClass', $reflection->getName());
    }

    public function testInvokeFindsTrait()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 19);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooTrait', $reflection->getName());
    }

    public function testInvokeFindsInterface()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 24);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooInterface', $reflection->getName());
    }

    public function testInvokeFindsMethod()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 14);

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('someMethod', $reflection->getName());
    }

    public function testInvokeFindsFunction()
    {
        $finder = new FindReflectionOnLine();
        $reflection = $finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 5);

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame('fooFunc', $reflection->getName());
    }

    public function testInvokeReturnsNullWhenNothingFound()
    {
        $finder = new FindReflectionOnLine();
        self::assertNull($finder(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 1));
    }
}
