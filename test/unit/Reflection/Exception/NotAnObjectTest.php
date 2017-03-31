<?php

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAnObject
 */
class NotAnObjectTest extends PHPUnit_Framework_TestCase
{
    public function testFromNonObject()
    {
        $exception = NotAnObject::fromNonObject(123);

        self::assertInstanceOf(NotAnObject::class, $exception);
        self::assertSame('Provided "integer" is not an object', $exception->getMessage());
    }
}
