<?php

namespace BetterReflectionTest\Reflection\Exception;

use BetterReflection\Reflection\Exception\NotAString;
use BetterReflectionTest\ClassWithInterfaces;
use BetterReflectionTest\ClassWithInterfacesOther;
use PHPUnit_Framework_TestCase;

/**
 * @covers \BetterReflection\Reflection\Exception\NotAString
 */
class NotAStringTest extends PHPUnit_Framework_TestCase
{
    public function testFromNonStringWithInteger()
    {
        $exception = NotAString::fromNonString(123);

        $this->assertInstanceOf(NotAString::class, $exception);
        $this->assertSame('Provided "integer" is not a string', $exception->getMessage());
    }

    public function testFromNonStringWithObject()
    {
        $exception = NotAString::fromNonString($this);

        $this->assertInstanceOf(NotAString::class, $exception);
        $this->assertSame(sprintf('Provided "%s" is not a string', __CLASS__), $exception->getMessage());
    }
}
