<?php

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\NotAString;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAString
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
