<?php

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\Uncloneable
 */
class UncloneableTest extends PHPUnit_Framework_TestCase
{
    public function testFromNonObject()
    {
        $exception = Uncloneable::fromClass('foo');

        $this->assertInstanceOf(Uncloneable::class, $exception);
        $this->assertSame('Trying to clone an uncloneable object of class foo', $exception->getMessage());
    }
}
