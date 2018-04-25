<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Util\FindReflectionOnLine;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\Util\FindReflectionOnLine
 */
class FindReflectionOnLineTest extends TestCase
{
    /** @var FindReflectionOnLine */
    private $finder;

    protected function setUp() : void
    {
        parent::setUp();

        $this->finder = BetterReflectionSingleton::instance()->findReflectionsOnLine();
    }

    public function testInvokeFindsClass() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 10);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooClass', $reflection->getName());
    }

    public function testInvokeFindsTrait() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 19);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooTrait', $reflection->getName());
    }

    public function testInvokeFindsInterface() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 24);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooInterface', $reflection->getName());
    }

    public function testInvokeFindsMethod() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 14);

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('someMethod', $reflection->getName());
    }

    public function testInvokeFindsFunction() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 5);

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame('fooFunc', $reflection->getName());
    }

    public function testInvokeReturnsNullWhenNothingFound() : void
    {
        self::assertNull(($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 1));
    }

    public function testInvokeFindsClassWithImplementedInterface() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 26);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooClassWithImplementedInterface', $reflection->getName());
    }
}
